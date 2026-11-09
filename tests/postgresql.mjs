import { PGlite } from '@electric-sql/pglite';
import fs from 'node:fs';
import path from 'node:path';
import assert from 'node:assert/strict';
const root = path.resolve(process.argv[2] || '..');
const db = new PGlite();
const schema = fs.readFileSync(path.join(root, 'database/postgresql.sql'), 'utf8');
await db.exec(schema);
await db.exec(schema);
await db.exec('CREATE TABLE harvee_migrations (version VARCHAR(100) PRIMARY KEY)');
let checked = 0;
function walk(dir) {
  return fs.readdirSync(dir, {withFileTypes:true}).flatMap(e => e.name.startsWith('.') || ['node_modules','tests'].includes(e.name) ? [] : e.isDirectory() ? walk(path.join(dir,e.name)) : [path.join(dir,e.name)]);
}
for (const file of walk(root).filter(f => f.endsWith('.php'))) {
  const source = fs.readFileSync(file,'utf8');
  for (const m of source.matchAll(/->(?:prepare|query)\(\s*(['"])([\s\S]*?)\1\s*\)/g)) {
    let sql = m[2].replaceAll('\\n','\n');
    if (sql.includes('$') || !/^\s*(SELECT|INSERT|UPDATE|DELETE)/i.test(sql)) continue;
    let n=0; sql=sql.replace(/\?/g,()=>'$'+(++n));
    try { await db.query('EXPLAIN '+sql,Array(n).fill(null)); checked++; }
    catch(e) { throw new Error(path.relative(root,file)+': '+e.message+'\n'+sql); }
  }
}
const user = async(role,email) => (await db.query("INSERT INTO users(first_name,last_name,username,email,password,role) VALUES('Test','User',$1,$1,'hash',$2) RETURNING id,name",[email,role])).rows[0];
const farmer=await user('farmer','farmer@example.test');
const customer=await user('customer','customer@example.test');
assert.equal(customer.name,'Test User');
const product=(await db.query("INSERT INTO products(farmer_id,name,slug,price,stock_quantity) VALUES($1,'Carrots','carrots',25,10) RETURNING id",[farmer.id])).rows[0];
await db.query("INSERT INTO addresses(user_id,recipient_name,phone,address_line1,city,province,is_default) VALUES($1,'Test User','09123456789','123 Test St','Manila','Metro Manila',1)",[customer.id]);
await db.query('INSERT INTO cart(user_id,product_id,quantity,price_at_time) VALUES($1,$2,2,25)',[customer.id,product.id]);
await db.exec('BEGIN');
const cart=(await db.query('SELECT c.*,p.price,p.stock_quantity FROM cart c JOIN products p ON p.id=c.product_id WHERE c.user_id=$1 ORDER BY p.id FOR UPDATE',[customer.id])).rows;
assert.equal(cart.length,1);
const order=(await db.query("INSERT INTO orders(order_number,customer_id,subtotal,total,shipping_address) VALUES('HVTEST',$1,50,50,'123 Test St, Manila') RETURNING id",[customer.id])).rows[0];
await db.query("INSERT INTO order_items(order_id,product_id,farmer_id,product_name,quantity,unit_price,total_price) VALUES($1,$2,$3,'Carrots',2,25,50)",[order.id,product.id,farmer.id]);
await db.query('UPDATE products SET stock_quantity=stock_quantity-2 WHERE id=$1',[product.id]);
await db.query('DELETE FROM cart WHERE user_id=$1',[customer.id]);
await db.exec('COMMIT');
assert.equal((await db.query('SELECT stock_quantity FROM products WHERE id=$1',[product.id])).rows[0].stock_quantity,8);
await db.query("UPDATE users SET first_name='Updated' WHERE id=$1",[customer.id]);
assert.equal((await db.query('SELECT name FROM users WHERE id=$1',[customer.id])).rows[0].name,'Updated User');
assert.equal((await db.query("SELECT COUNT(*)::int AS n FROM products WHERE name ILIKE '%CARROT%' ")).rows[0].n,1);
await assert.rejects(db.query("INSERT INTO users(first_name,last_name,username,email,password,role) VALUES('Bad','Role','bad','bad@example.test','hash','invalid')"));
console.log(`PASS: schema imports twice; ${checked} static SQL statements planned; registration IDs, generated names, address/cart/checkout, stock and case-insensitive search validated.`);
await db.close();
