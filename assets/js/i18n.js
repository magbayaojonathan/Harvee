(function () {
    const config = window.HARVEE_I18N_CONFIG || {};
    const STORAGE_KEY = 'harvee_language';
    const COOKIE_KEY = 'harvee_language';
    const SELECTOR_ID = 'harveeFloatingLanguageSelect';
    const DOCK_ID = 'harveeLanguageDock';
    const LABEL_ID = 'harveeLanguageDockLabel';

    const LANGUAGE_LABELS = {
        en: 'English',
        fil: 'Filipino',
        ceb: 'Bisaya'
    };

    const EXACT_TRANSLATIONS = {
        fil: {
            'Language': 'Wika',
            'Choose language': 'Pumili ng wika',
            'Home': 'Home',
            'Products': 'Mga Produkto',
            'Features': 'Mga Tampok',
            'How It Works': 'Paano Ito Gumagana',
            'About': 'Tungkol',
            'About Us': 'Tungkol sa Amin',
            'About Harvee': 'Tungkol sa Harvee',
            'Contact': 'Kontak',
            'Contact Us': 'Makipag-ugnayan',
            'Contact Info': 'Impormasyon sa Kontak',
            'Dashboard': 'Dashboard',
            'Farmer Dashboard': 'Dashboard ng Magsasaka',
            'Driver Dashboard': 'Dashboard ng Driver',
            'Login': 'Mag-login',
            'Logout': 'Mag-logout',
            'Register': 'Magrehistro',
            'Sign Up': 'Mag-sign Up',
            'Shopping': 'Pamimili',
            'Cart': 'Cart',
            'My Cart': 'Aking Cart',
            'My Orders': 'Aking Mga Order',
            'Order Details': 'Detalye ng Order',
            'Order Confirmation': 'Kumpirmasyon ng Order',
            'Addresses': 'Mga Address',
            'My Addresses': 'Aking Mga Address',
            'Wishlist': 'Wishlist',
            'My Wishlist': 'Aking Wishlist',
            'Profile': 'Profile',
            'My Profile': 'Aking Profile',
            'My Farm Profile': 'Aking Farm Profile',
            'Settings': 'Mga Setting',
            'Earnings': 'Kita',
            'My Customers': 'Aking Mga Customer',
            'Farmer Orders': 'Mga Order ng Magsasaka',
            'Product Reviews': 'Mga Review ng Produkto',
            'Manage Products': 'Pamahalaan ang Mga Produkto',
            'My Products': 'Aking Mga Produkto',
            'Add Product': 'Magdagdag ng Produkto',
            'Add New Product': 'Magdagdag ng Bagong Produkto',
            'Edit Product': 'I-edit ang Produkto',
            'View Products': 'Tingnan ang Mga Produkto',
            'Browse Products': 'Mag-browse ng Mga Produkto',
            'Click, Farm and Enjoy!': 'Click, ani, at enjoy!',
            'Connect directly with local farmers and get the freshest produce delivered to your doorstep.': 'Makipag-ugnayan nang direkta sa mga lokal na magsasaka at makatanggap ng pinakasariwang ani sa inyong pintuan.',
            'Search for crops': 'Maghanap ng ani',
            'Search crops': 'Maghanap ng ani',
            'Search products...': 'Maghanap ng mga produkto...',
            'Search order #, customer, or address': 'Maghanap ng order #, customer, o address',
            'Search barangay, street, city': 'Maghanap ng barangay, kalye, lungsod',
            'Search by name, email, or phone': 'Maghanap ayon sa pangalan, email, o telepono',
            'Search': 'Maghanap',
            'Fresh Products': 'Sariwang Produkto',
            'Featured Products': 'Mga Tampok na Produkto',
            'View All Products': 'Tingnan ang Lahat ng Produkto',
            'Low Stock': 'Mababang Stock',
            'Out of Stock': 'Walang Stock',
            'In Stock': 'May Stock',
            'per unit': 'bawat yunit',
            'Description': 'Paglalarawan',
            'No description available': 'Walang available na paglalarawan',
            'Available Stock': 'Available na Stock',
            'About the Seller': 'Tungkol sa Seller',
            'Fresh Produce Provider': 'Tagapagbigay ng Sariwang Ani',
            'Trusted Farm Seller': 'Mapagkakatiwalaang Farm Seller',
            'More from this Seller': 'Mas marami mula sa seller na ito',
            'Quantity': 'Dami',
            'Add to Cart': 'Idagdag sa Cart',
            'Sign In to Add to Cart': 'Mag-sign in para maidagdag sa cart',
            'Product ID': 'ID ng Produkto',
            'Added': 'Idinagdag',
            'Last Updated': 'Huling In-update',
            'Product not found.': 'Hindi nakita ang produkto.',
            'Error loading product details. Please try again.': 'Nagkaroon ng error sa pag-load ng detalye ng produkto. Pakisubukang muli.',
            'Cart updated successfully!': 'Matagumpay na na-update ang cart!',
            'Product added to cart!': 'Naidagdag ang produkto sa cart!',
            'Cannot add more than available stock!': 'Hindi maaaring lumampas sa available na stock!',
            'Item removed from cart!': 'Inalis ang item sa cart!',
            'Cart cleared!': 'Nalinis ang cart!',
            'Your cart is empty!': 'Walang laman ang inyong cart!',
            'Proceed to Checkout': 'Magpatuloy sa Checkout',
            'Checkout': 'Checkout',
            '100% Secure Checkout': '100% Ligtas na Checkout',
            'Subtotal': 'Subtotal',
            'Total': 'Kabuuan',
            'Rate Products': 'I-rate ang Mga Produkto',
            'Rate Purchased Products': 'I-rate ang Mga Nabiling Produkto',
            'Contact Farmer': 'Kontakin ang Magsasaka',
            'Message': 'Mensahe',
            'Send Message': 'Ipadala ang Mensahe',
            'Send us a message': 'Padalhan kami ng mensahe',
            'Message is required.': 'Kinakailangan ang mensahe.',
            'Message sent to farmer successfully.': 'Matagumpay na naipadala ang mensahe sa magsasaka.',
            'Type your message here...': 'I-type ang inyong mensahe dito...',
            'Share your experience with this product': 'Ibahagi ang inyong karanasan sa produktong ito',
            'Full Name': 'Buong Pangalan',
            'First Name': 'Pangalan',
            'Last Name': 'Apelyido',
            'Username': 'Username',
            'Email': 'Email',
            'Email Address': 'Email Address',
            'Password': 'Password',
            'Current Password': 'Kasalukuyang Password',
            'New Password': 'Bagong Password',
            'Confirm Password': 'Kumpirmahin ang Password',
            'Phone': 'Telepono',
            'Phone Number': 'Numero ng Telepono',
            'Farm Name': 'Pangalan ng Bukid',
            'Subject': 'Paksa',
            'Security & Contact': 'Seguridad at Kontak',
            'Save Changes': 'I-save ang Mga Pagbabago',
            'Update Profile': 'I-update ang Profile',
            'Update Farm Profile': 'I-update ang Farm Profile',
            'Profile updated successfully!': 'Matagumpay na na-update ang profile!',
            'First name is required': 'Kinakailangan ang pangalan',
            'Last name is required': 'Kinakailangan ang apelyido',
            'Name is required': 'Kinakailangan ang pangalan',
            'Username is required': 'Kinakailangan ang username',
            'Email is required': 'Kinakailangan ang email',
            'Password is required': 'Kinakailangan ang password',
            'Phone number is required': 'Kinakailangan ang numero ng telepono',
            'Farm name is required for farmers': 'Kinakailangan ang pangalan ng bukid para sa mga magsasaka',
            'Category is required': 'Kinakailangan ang kategorya',
            'Description is required': 'Kinakailangan ang paglalarawan',
            'Product name is required': 'Kinakailangan ang pangalan ng produkto',
            'Unit is required': 'Kinakailangan ang yunit',
            'Login Successful!': 'Matagumpay ang pag-login!',
            'Login Failed': 'Nabigo ang pag-login',
            'Back to Dashboard': 'Bumalik sa Dashboard',
            'Go to Dashboard': 'Pumunta sa Dashboard',
            'Recently Added Products': 'Mga Kamakailang Idinagdag na Produkto',
            'Low Stock Alert': 'Babala sa Mababang Stock',
            'Products need restock': 'Kailangang i-restock ang mga produkto',
            'Total Products': 'Kabuuang Produkto',
            'Total Orders': 'Kabuuang Mga Order',
            'Total Revenue': 'Kabuuang Kita',
            'Completed orders': 'Nakumpletong mga order',
            'Lifetime earnings': 'Kabuuang kinita',
            'Active in your store': 'Aktibo sa iyong tindahan',
            'Local Farmers': 'Mga Lokal na Magsasaka',
            'Happy Customers': 'Masayang Mga Customer',
            'Cities Served': 'Mga Lungsod na Sineserbisyuhan',
            'Our Mission': 'Ating Misyon',
            'Our Vision': 'Ating Bisyon',
            'Our Story': 'Ating Kwento',
            'Our Core Values': 'Ating Pangunahing Halaga',
            'Meet the Team': 'Kilalanin ang Team',
            'Join the Harvee Community': 'Sumali sa Komunidad ng Harvee',
            'Become a Farmer': 'Maging Magsasaka',
            'Shop Now': 'Mamili Ngayon',
            'Sustainability': 'Pagpapanatili',
            'Fair Trade': 'Makatarungang Kalakalan',
            'Community': 'Komunidad',
            'Office': 'Opisina',
            'Frequently Asked Questions': 'Mga Madalas Itanong',
            'Follow Our Journey': 'Samahan ang Aming Paglalakbay',
            'Contact Support': 'Kontakin ang Suporta',
            'Harvee - Fresh from Farm to Table': 'Harvee - Sariwa mula Bukid hanggang Hapag',
            'Harvee - Login': 'Harvee - Login',
            'Harvee - Register': 'Harvee - Rehistro',
            'About Us - Harvee': 'Tungkol sa Amin - Harvee',
            'Contact Us - Harvee': 'Makipag-ugnayan - Harvee',
            'My Dashboard - Harvee Market': 'Aking Dashboard - Harvee Market',
            'My Cart - Harvee': 'Aking Cart - Harvee',
            'My Orders - Harvee': 'Aking Mga Order - Harvee',
            'My Profile - Harvee': 'Aking Profile - Harvee',
            'My Wishlist - Harvee': 'Aking Wishlist - Harvee',
            'My Addresses - Harvee': 'Aking Mga Address - Harvee',
            'Browse Products - Harvee': 'Mag-browse ng Mga Produkto - Harvee',
            'Contact Farmer - Harvee': 'Kontakin ang Magsasaka - Harvee',
            'Rate Products - Harvee': 'I-rate ang Mga Produkto - Harvee',
            'Farmer Dashboard - Harvee Farm': 'Dashboard ng Magsasaka - Harvee Farm',
            'Farmer Profile - Harvee': 'Profile ng Magsasaka - Harvee',
            'Farmer Orders - Harvee Farm': 'Mga Order ng Magsasaka - Harvee Farm',
            'Manage Products - Harvee Farm': 'Pamahalaan ang Mga Produkto - Harvee Farm',
            'Add New Product - Harvee Farm': 'Magdagdag ng Bagong Produkto - Harvee Farm',
            'Edit Product - Harvee Farm': 'I-edit ang Produkto - Harvee Farm',
            'Earnings - Harvee Farm': 'Kita - Harvee Farm',
            'Settings - Harvee Farm': 'Mga Setting - Harvee Farm',
            'Product Reviews - Harvee Farm': 'Mga Review ng Produkto - Harvee Farm',
            'My Customers - Harvee Farm': 'Aking Mga Customer - Harvee Farm',
            'Driver Dashboard - Harvee': 'Dashboard ng Driver - Harvee',
            'Email Verification': 'Beripikasyon ng Email',
            'English': 'English',
            'Filipino': 'Filipino',
            'Bisaya': 'Bisaya',
            'Vegetables': 'Mga Gulay',
            'Fruits': 'Mga Prutas',
            'Herbs': 'Mga Halamang Lasa',
            'Root Crops': 'Mga Ugat na Pananim',
            'Leafy Greens': 'Mga Madahong Gulay',
            'Organic Produce': 'Organikong Ani',
            'Dairy Products': 'Mga Produktong Gatas',
            'Eggs': 'Mga Itlog',
            'Honey': 'Pulot',
            'Seeds': 'Mga Binhi',
            'Grains': 'Mga Butil',
            'Poultry': 'Manok at Ibon',
            'Dairy': 'Gatasan',
            'Legumes': 'Mga Sitaw at Butil'
        },
        ceb: {
            'Language': 'Pinulongan',
            'Choose language': 'Pilia ang pinulongan',
            'Home': 'Balay',
            'Products': 'Mga Produkto',
            'Features': 'Mga Feature',
            'How It Works': 'Giunsa Kini Pagtrabaho',
            'About': 'Mahitungod',
            'About Us': 'Mahitungod Kanamo',
            'About Harvee': 'Mahitungod sa Harvee',
            'Contact': 'Kontak',
            'Contact Us': 'Kontaka Kami',
            'Contact Info': 'Impormasyon sa Kontak',
            'Dashboard': 'Dashboard',
            'Farmer Dashboard': 'Dashboard sa Mag-uuma',
            'Driver Dashboard': 'Dashboard sa Driver',
            'Login': 'Sulod',
            'Logout': 'Gawas',
            'Register': 'Rehistro',
            'Sign Up': 'Pag-sign Up',
            'Shopping': 'Pagpalit',
            'Cart': 'Cart',
            'My Cart': 'Akong Cart',
            'My Orders': 'Akong mga Order',
            'Order Details': 'Detalye sa Order',
            'Order Confirmation': 'Kumpirmasyon sa Order',
            'Addresses': 'Mga Address',
            'My Addresses': 'Akong mga Address',
            'Wishlist': 'Wishlist',
            'My Wishlist': 'Akong Wishlist',
            'Profile': 'Profile',
            'My Profile': 'Akong Profile',
            'My Farm Profile': 'Akong Farm Profile',
            'Settings': 'Mga Setting',
            'Earnings': 'Kinitaan',
            'My Customers': 'Akong mga Customer',
            'Farmer Orders': 'Mga Order sa Mag-uuma',
            'Product Reviews': 'Mga Review sa Produkto',
            'Manage Products': 'Dumala sa mga Produkto',
            'My Products': 'Akong mga Produkto',
            'Add Product': 'Dugang Produkto',
            'Add New Product': 'Dugang Bag-ong Produkto',
            'Edit Product': 'Usba ang Produkto',
            'View Products': 'Tan-awa ang mga Produkto',
            'Browse Products': 'Tan-awa ang mga Produkto',
            'Click, Farm and Enjoy!': 'Click, ani, ug lingaw!',
            'Connect directly with local farmers and get the freshest produce delivered to your doorstep.': 'Makigkonektar diretso sa lokal nga mga mag-uuma ug madawat ang labing preskong ani sa inyong pultahan.',
            'Search for crops': 'Pangita og ani',
            'Search crops': 'Pangita og ani',
            'Search products...': 'Pangita og mga produkto...',
            'Search order #, customer, or address': 'Pangita og order #, customer, o address',
            'Search barangay, street, city': 'Pangita og barangay, dalan, syudad',
            'Search by name, email, or phone': 'Pangita pinaagi sa ngalan, email, o telepono',
            'Search': 'Pangita',
            'Fresh Products': 'Preskong mga Produkto',
            'Featured Products': 'Gipasiugda nga mga Produkto',
            'View All Products': 'Tan-awa ang Tanan nga Produkto',
            'Low Stock': 'Ubos na ang Stock',
            'Out of Stock': 'Walay Stock',
            'In Stock': 'Naay Stock',
            'per unit': 'kada yunit',
            'Description': 'Deskripsyon',
            'No description available': 'Walay deskripsyon',
            'Available Stock': 'Anaa nga Stock',
            'About the Seller': 'Mahitungod sa Seller',
            'Fresh Produce Provider': 'Tighatag og Preskong Ani',
            'Trusted Farm Seller': 'Kasaligan nga Farm Seller',
            'More from this Seller': 'Dugang pa gikan niining seller',
            'Quantity': 'Kadaghanon',
            'Add to Cart': 'Idugang sa Cart',
            'Sign In to Add to Cart': 'Sulod aron madugang sa cart',
            'Product ID': 'ID sa Produkto',
            'Added': 'Gidugang',
            'Last Updated': 'Katapusang Gi-update',
            'Product not found.': 'Wala makita ang produkto.',
            'Error loading product details. Please try again.': 'Naay sayop sa pag-load sa detalye sa produkto. Palihug sulayi pag-usab.',
            'Cart updated successfully!': 'Malampusong na-update ang cart!',
            'Product added to cart!': 'Nadugang ang produkto sa cart!',
            'Cannot add more than available stock!': 'Dili pwede molapas sa anaa nga stock!',
            'Item removed from cart!': 'Natangtang ang item sa cart!',
            'Cart cleared!': 'Nahawan ang cart!',
            'Your cart is empty!': 'Walay sulod ang inyong cart!',
            'Proceed to Checkout': 'Padayon sa Checkout',
            'Checkout': 'Checkout',
            '100% Secure Checkout': '100% Luwas nga Checkout',
            'Subtotal': 'Subtotal',
            'Total': 'Total',
            'Rate Products': 'I-rate ang mga Produkto',
            'Rate Purchased Products': 'I-rate ang Napalit nga mga Produkto',
            'Contact Farmer': 'Kontaka ang Mag-uuma',
            'Message': 'Mensahe',
            'Send Message': 'Ipadala ang Mensahe',
            'Send us a message': 'Padad-i kami og mensahe',
            'Message is required.': 'Kinahanglan ang mensahe.',
            'Message sent to farmer successfully.': 'Malampusong naipadala ang mensahe sa mag-uuma.',
            'Type your message here...': 'I-type dinhi ang inyong mensahe...',
            'Share your experience with this product': 'Ipaambit ang inyong kasinatian niining produkto',
            'Full Name': 'Tibuok Ngalan',
            'First Name': 'Ngalan',
            'Last Name': 'Apelyido',
            'Username': 'Username',
            'Email': 'Email',
            'Email Address': 'Email Address',
            'Password': 'Password',
            'Current Password': 'Karon nga Password',
            'New Password': 'Bag-ong Password',
            'Confirm Password': 'Kumpirma ang Password',
            'Phone': 'Telepono',
            'Phone Number': 'Numero sa Telepono',
            'Farm Name': 'Ngalan sa Uma',
            'Subject': 'Hilisgutan',
            'Security & Contact': 'Seguridad ug Kontak',
            'Save Changes': 'I-save ang mga Kausaban',
            'Update Profile': 'I-update ang Profile',
            'Update Farm Profile': 'I-update ang Farm Profile',
            'Profile updated successfully!': 'Malampusong na-update ang profile!',
            'First name is required': 'Kinahanglan ang ngalan',
            'Last name is required': 'Kinahanglan ang apelyido',
            'Name is required': 'Kinahanglan ang ngalan',
            'Username is required': 'Kinahanglan ang username',
            'Email is required': 'Kinahanglan ang email',
            'Password is required': 'Kinahanglan ang password',
            'Phone number is required': 'Kinahanglan ang numero sa telepono',
            'Farm name is required for farmers': 'Kinahanglan ang ngalan sa uma para sa mga mag-uuma',
            'Category is required': 'Kinahanglan ang kategorya',
            'Description is required': 'Kinahanglan ang deskripsyon',
            'Product name is required': 'Kinahanglan ang ngalan sa produkto',
            'Unit is required': 'Kinahanglan ang yunit',
            'Login Successful!': 'Malampuson ang pag-login!',
            'Login Failed': 'Napakyas ang pag-login',
            'Back to Dashboard': 'Balik sa Dashboard',
            'Go to Dashboard': 'Adto sa Dashboard',
            'Recently Added Products': 'Bag-ong Gidugang nga mga Produkto',
            'Low Stock Alert': 'Pahimangno sa Ubos nga Stock',
            'Products need restock': 'Kinahanglan og restock ang mga produkto',
            'Total Products': 'Total nga mga Produkto',
            'Total Orders': 'Total nga mga Order',
            'Total Revenue': 'Total nga Kita',
            'Completed orders': 'Nahuman nga mga order',
            'Lifetime earnings': 'Tibuok kinitaan',
            'Active in your store': 'Aktibo sa inyong tindahan',
            'Local Farmers': 'Lokal nga mga Mag-uuma',
            'Happy Customers': 'Malipayong mga Customer',
            'Cities Served': 'Mga Syudad nga Giserbisyuhan',
            'Our Mission': 'Among Misyon',
            'Our Vision': 'Among Bisyon',
            'Our Story': 'Among Sugilanon',
            'Our Core Values': 'Among Pangunang mga Bili',
            'Meet the Team': 'Ila-ila ang Team',
            'Join the Harvee Community': 'Apil sa Komunidad sa Harvee',
            'Become a Farmer': 'Mahimong Mag-uuma',
            'Shop Now': 'Palit Karon',
            'Sustainability': 'Pagpadayon',
            'Fair Trade': 'Makatarunganong Negosyo',
            'Community': 'Komunidad',
            'Office': 'Opisina',
            'Frequently Asked Questions': 'Kanunayng Gipangutana',
            'Follow Our Journey': 'Sunda ang Among Panaw',
            'Contact Support': 'Kontaka ang Suporta',
            'Harvee - Fresh from Farm to Table': 'Harvee - Presko gikan sa Uma ngadto sa Lamesa',
            'Harvee - Login': 'Harvee - Sulod',
            'Harvee - Register': 'Harvee - Rehistro',
            'About Us - Harvee': 'Mahitungod Kanamo - Harvee',
            'Contact Us - Harvee': 'Kontaka Kami - Harvee',
            'My Dashboard - Harvee Market': 'Akong Dashboard - Harvee Market',
            'My Cart - Harvee': 'Akong Cart - Harvee',
            'My Orders - Harvee': 'Akong mga Order - Harvee',
            'My Profile - Harvee': 'Akong Profile - Harvee',
            'My Wishlist - Harvee': 'Akong Wishlist - Harvee',
            'My Addresses - Harvee': 'Akong mga Address - Harvee',
            'Browse Products - Harvee': 'Tan-awa ang mga Produkto - Harvee',
            'Contact Farmer - Harvee': 'Kontaka ang Mag-uuma - Harvee',
            'Rate Products - Harvee': 'I-rate ang mga Produkto - Harvee',
            'Farmer Dashboard - Harvee Farm': 'Dashboard sa Mag-uuma - Harvee Farm',
            'Farmer Profile - Harvee': 'Profile sa Mag-uuma - Harvee',
            'Farmer Orders - Harvee Farm': 'Mga Order sa Mag-uuma - Harvee Farm',
            'Manage Products - Harvee Farm': 'Dumala sa mga Produkto - Harvee Farm',
            'Add New Product - Harvee Farm': 'Dugang Bag-ong Produkto - Harvee Farm',
            'Edit Product - Harvee Farm': 'Usba ang Produkto - Harvee Farm',
            'Earnings - Harvee Farm': 'Kinitaan - Harvee Farm',
            'Settings - Harvee Farm': 'Mga Setting - Harvee Farm',
            'Product Reviews - Harvee Farm': 'Mga Review sa Produkto - Harvee Farm',
            'My Customers - Harvee Farm': 'Akong mga Customer - Harvee Farm',
            'Driver Dashboard - Harvee': 'Dashboard sa Driver - Harvee',
            'Email Verification': 'Pag-verify sa Email',
            'English': 'English',
            'Filipino': 'Filipino',
            'Bisaya': 'Bisaya',
            'Vegetables': 'Mga Utanon',
            'Fruits': 'Mga Prutas',
            'Herbs': 'Mga Hilamon nga Panakot',
            'Root Crops': 'Mga Root Crops',
            'Leafy Greens': 'Madahong Utanon',
            'Organic Produce': 'Organikong Ani',
            'Dairy Products': 'Mga Produktong Gatas',
            'Eggs': 'Mga Itlog',
            'Honey': 'Dugos',
            'Seeds': 'Mga Liso',
            'Grains': 'Mga Trigo ug Butil',
            'Poultry': 'Manok ug Poultry',
            'Dairy': 'Gatasan',
            'Legumes': 'Mga Legumbre'
        }
    };

    const REGEX_TRANSLATIONS = {
        fil: [
            {
                pattern: /^(\d+)\s+featured crops matched "(.+)"\.$/i,
                replace: (_, count, query) => `${count} tampok na ani ang tumugma sa "${query}".`
            },
            {
                pattern: /^(\d+)\s+featured crop matched "(.+)"\.$/i,
                replace: (_, count, query) => `${count} tampok na ani ang tumugma sa "${query}".`
            },
            {
                pattern: /^Not enough stock available! Only (\d+) items left\.$/i,
                replace: (_, count) => `Hindi sapat ang stock! ${count} na item na lang ang natitira.`
            },
            {
                pattern: /^Low Stock:\s*(\d+)\s+left$/i,
                replace: (_, count) => `Mababang Stock: ${count} na lang`
            },
            {
                pattern: /^In Stock \((\d+)\s+units\)$/i,
                replace: (_, count) => `May Stock (${count} yunit)`
            },
            {
                pattern: /^Checkout failed:\s*(.+)$/i,
                replace: (_, reason) => `Hindi nagtagumpay ang checkout: ${reason}`
            },
            {
                pattern: /^Error updating profile:\s*(.+)$/i,
                replace: (_, reason) => `Error sa pag-update ng profile: ${reason}`
            },
            {
                pattern: /^Error changing password:\s*(.+)$/i,
                replace: (_, reason) => `Error sa pagpapalit ng password: ${reason}`
            },
            {
                pattern: /^Order Details #(.+) - Harvee$/i,
                replace: (_, orderNumber) => `Detalye ng Order #${orderNumber} - Harvee`
            },
            {
                pattern: /^Order Confirmation - (.+)$/i,
                replace: (_, receipt) => `Kumpirmasyon ng Order - ${receipt}`
            },
            {
                pattern: /^Search:\s*"(.+)"$/i,
                replace: (_, query) => `Hanap: "${query}"`
            },
            {
                pattern: /^Searching for "(.+)"$/i,
                replace: (_, query) => `Hinahanap ang "${query}"`
            },
            {
                pattern: /^Use the search bar above to narrow these featured crops instantly\.$/i,
                replace: () => 'Gamitin ang search bar sa itaas para mapaliit agad ang mga tampok na ani.'
            }
        ],
        ceb: [
            {
                pattern: /^(\d+)\s+featured crops matched "(.+)"\.$/i,
                replace: (_, count, query) => `${count} ka gipasiugda nga ani ang mitugma sa "${query}".`
            },
            {
                pattern: /^(\d+)\s+featured crop matched "(.+)"\.$/i,
                replace: (_, count, query) => `${count} ka gipasiugda nga ani ang mitugma sa "${query}".`
            },
            {
                pattern: /^Not enough stock available! Only (\d+) items left\.$/i,
                replace: (_, count) => `Dili igo ang stock! ${count} na lang ka item ang nabilin.`
            },
            {
                pattern: /^Low Stock:\s*(\d+)\s+left$/i,
                replace: (_, count) => `Ubos na ang Stock: ${count} na lang`
            },
            {
                pattern: /^In Stock \((\d+)\s+units\)$/i,
                replace: (_, count) => `Naay Stock (${count} ka yunit)`
            },
            {
                pattern: /^Checkout failed:\s*(.+)$/i,
                replace: (_, reason) => `Napakyas ang checkout: ${reason}`
            },
            {
                pattern: /^Error updating profile:\s*(.+)$/i,
                replace: (_, reason) => `Sayop sa pag-update sa profile: ${reason}`
            },
            {
                pattern: /^Error changing password:\s*(.+)$/i,
                replace: (_, reason) => `Sayop sa pag-ilis sa password: ${reason}`
            },
            {
                pattern: /^Order Details #(.+) - Harvee$/i,
                replace: (_, orderNumber) => `Detalye sa Order #${orderNumber} - Harvee`
            },
            {
                pattern: /^Order Confirmation - (.+)$/i,
                replace: (_, receipt) => `Kumpirmasyon sa Order - ${receipt}`
            },
            {
                pattern: /^Search:\s*"(.+)"$/i,
                replace: (_, query) => `Pangita: "${query}"`
            },
            {
                pattern: /^Searching for "(.+)"$/i,
                replace: (_, query) => `Nangita para sa "${query}"`
            },
            {
                pattern: /^Use the search bar above to narrow these featured crops instantly\.$/i,
                replace: () => 'Gamita ang search bar sa taas aron dali mapili ang gipasiugda nga mga ani.'
            }
        ]
    };

    const PHRASE_TRANSLATIONS = {
        fil: {
            'Shopping Cart': 'Shopping Cart',
            'Continue Shopping': 'Ipagpatuloy ang Pamimili',
            'View all products': 'Tingnan ang lahat ng produkto',
            'View all orders': 'Tingnan ang lahat ng order',
            'View earnings': 'Tingnan ang kita',
            'View low stock': 'Tingnan ang mababang stock',
            'Your cart is empty': 'Walang laman ang inyong cart',
            'Looks like you haven\'t added any products to your cart yet.': 'Mukhang wala pa kayong naidaragdag na produkto sa inyong cart.',
            'Start shopping to find amazing products from local farmers!': 'Simulan ang pamimili para makahanap ng magagandang produkto mula sa mga lokal na magsasaka!',
            'return to dashboard': 'bumalik sa dashboard',
            'Your Items': 'Inyong Mga Item',
            'Update Cart': 'I-update ang Cart',
            'Farmer:': 'Magsasaka:',
            'Max:': 'Max:',
            'per order': 'bawat order',
            'per item': 'bawat item',
            'Prices and availability are subject to change': 'Maaaring magbago ang presyo at availability',
            'Clear Cart': 'I-clear ang Cart',
            'Order Summary': 'Buod ng Order',
            'Shipping Fee': 'Bayad sa Shipping',
            'Service Fee': 'Bayad sa Serbisyo',
            'FREE': 'LIBRE',
            'Spend': 'Gumastos ng',
            'more for FREE shipping!': 'pa para sa LIBRENG shipping!',
            'You\'ve earned FREE shipping!': 'Nakuha ninyo ang LIBRENG shipping!',
            'By placing your order, you agree to our Terms of Service': 'Sa pag-order, sumasang-ayon kayo sa aming Terms of Service',
            'Accepted Payment Methods': 'Mga Tinatanggap na Paraan ng Bayad',
            'Cash on Delivery': 'Cash on Delivery',
            'Bank Transfer': 'Bank Transfer',
            'Maximum quantity is': 'Ang maximum na dami ay',
            'due to stock limits.': 'dahil sa limitasyon ng stock.',
            'Are you sure you want to clear your entire cart?': 'Sigurado ba kayong gustong i-clear ang buong cart?',
            'Proceed to checkout?': 'Magpatuloy sa checkout?',
            'You will be redirected to complete your order.': 'Ire-redirect kayo upang kumpletuhin ang inyong order.',
            'Recent Orders': 'Mga Kamakailang Order',
            'View All': 'Tingnan Lahat',
            'No orders yet': 'Wala pang mga order',
            'Start shopping to see your orders here': 'Simulan ang pamimili para makita ang inyong mga order dito',
            'Recommended for You': 'Inirerekomenda para sa Inyo',
            'View More': 'Tingnan Pa',
            'Quick Actions': 'Mabilisang Aksyon',
            'Browse': 'Mag-browse',
            'Track': 'Subaybayan',
            'Shop by Category': 'Mamili ayon sa Kategorya',
            'Items in Cart': 'Mga Item sa Cart',
            'Spent': 'Nagastos',
            'Total Spent': 'Kabuuang Nagastos',
            'Browse our wide selection of fresh produce categories': 'I-browse ang aming malawak na pagpipilian ng mga kategorya ng sariwang ani',
            'Why Choose Harvee?': 'Bakit Piliin ang Harvee?',
            'Experience the best way to buy fresh produce directly from local farmers': 'Maranasan ang pinakamagandang paraan ng pagbili ng sariwang ani direkta mula sa mga lokal na magsasaka',
            '100% Fresh': '100% Sariwa',
            'Harvested at peak ripeness and delivered straight to you': 'Inani sa tamang pagkahinog at direktang inihatid sa inyo',
            'Support Local': 'Suportahan ang Lokal',
            'Help local farmers thrive and strengthen your community': 'Tulungan ang mga lokal na magsasaka na umunlad at palakasin ang inyong komunidad',
            'Best Prices': 'Pinakamagandang Presyo',
            'No middlemen means better prices for everyone': 'Walang middlemen kaya mas magandang presyo para sa lahat',
            'Fast Delivery': 'Mabilis na Delivery',
            'Quick delivery while maintaining product freshness': 'Mabilis na delivery habang napapanatili ang kasariwaan ng produkto',
            'Professional marketplace flow': 'Propesyonal na daloy ng marketplace',
            'Guests can explore crops first, then sign in or sign up when they are ready to add items to cart and check out.': 'Maaaring mag-explore muna ng mga ani ang mga bisita, saka mag-sign in o mag-sign up kapag handa nang magdagdag sa cart at mag-checkout.',
            'Click, Farm, Enjoy!': 'Click, ani, enjoy!',
            'Cart is available for customer accounts': 'Available ang cart para sa customer accounts',
            'View Details': 'Tingnan ang Detalye',
            'Connecting local farmers directly with customers for a fresher, fairer, farm-to-table experience.': 'Ikinokonekta ang mga lokal na magsasaka nang direkta sa mga customer para sa mas sariwa at mas patas na farm-to-table na karanasan.',
            'No products added yet': 'Wala pang naidaragdag na produkto',
            'Add Your First Product': 'Idagdag ang Iyong Unang Produkto',
            'All products have sufficient stock': 'May sapat na stock ang lahat ng produkto',
            'Restock': 'Mag-restock',
            'Pending Orders': 'Mga Nakabinbing Order',
            'Farmer Tips': 'Mga Tip para sa Magsasaka',
            'Keep your products updated with fresh photos': 'Panatilihing updated ang inyong mga produkto gamit ang sariwang larawan',
            'Respond to customer inquiries quickly': 'Mabilis na tumugon sa mga tanong ng customer',
            'Monitor low stock items to avoid missed sales': 'Bantayan ang mga item na mababa ang stock upang maiwasan ang napalampas na benta',
            'Check pending orders daily': 'Suriin araw-araw ang mga nakabinbing order',
            'Need more help? Visit Farmer Support': 'Kailangan pa ba ng tulong? Bisitahin ang Farmer Support',
            'Empowering farmers to reach more customers with fresh, quality products.': 'Binibigyang-lakas ang mga magsasaka na makaabot sa mas maraming customer gamit ang sariwa at de-kalidad na produkto.',
            'Quick Links': 'Mabilisang Link',
            'Support': 'Suporta',
            'Help Center': 'Help Center',
            'FAQ': 'FAQ',
            'Farming District, Philippines': 'Distrito ng Pagsasaka, Pilipinas',
            'Growing together with our farmers.': 'Sabay-sabay na umuunlad kasama ang aming mga magsasaka.',
            'orders': 'mga order',
            'items': 'mga item',
            'in stock': 'may stock',
            'left': 'natitira'
        },
        ceb: {
            'Shopping Cart': 'Shopping Cart',
            'Continue Shopping': 'Padayon sa Pagpalit',
            'View all products': 'Tan-awa ang tanan nga produkto',
            'View all orders': 'Tan-awa ang tanan nga order',
            'View earnings': 'Tan-awa ang kinitaan',
            'View low stock': 'Tan-awa ang ubos nga stock',
            'Your cart is empty': 'Walay sulod ang inyong cart',
            'Looks like you haven\'t added any products to your cart yet.': 'Murag wala pa kamo makadugang og mga produkto sa inyong cart.',
            'Start shopping to find amazing products from local farmers!': 'Sugdi ang pagpalit aron makakita og nindot nga mga produkto gikan sa lokal nga mga mag-uuma!',
            'return to dashboard': 'balik sa dashboard',
            'Your Items': 'Inyong mga Item',
            'Update Cart': 'I-update ang Cart',
            'Farmer:': 'Mag-uuma:',
            'Max:': 'Max:',
            'per order': 'kada order',
            'per item': 'kada item',
            'Prices and availability are subject to change': 'Ang presyo ug availability mahimong mausab',
            'Clear Cart': 'Hawana ang Cart',
            'Order Summary': 'Summary sa Order',
            'Shipping Fee': 'Bayad sa Shipping',
            'Service Fee': 'Bayad sa Serbisyo',
            'FREE': 'LIBRE',
            'Spend': 'Gasto og',
            'more for FREE shipping!': 'pa para sa LIBRENG shipping!',
            'You\'ve earned FREE shipping!': 'Nakadawat kamo og LIBRENG shipping!',
            'By placing your order, you agree to our Terms of Service': 'Sa pagbutang sa inyong order, miuyon kamo sa among Terms of Service',
            'Accepted Payment Methods': 'Gidawat nga Paagi sa Bayad',
            'Cash on Delivery': 'Cash on Delivery',
            'Bank Transfer': 'Bank Transfer',
            'Maximum quantity is': 'Ang pinakataas nga gidaghanon kay',
            'due to stock limits.': 'tungod sa limitasyon sa stock.',
            'Are you sure you want to clear your entire cart?': 'Sigurado ba kamo nga gusto ninyong hawanan ang tibuok cart?',
            'Proceed to checkout?': 'Padayon sa checkout?',
            'You will be redirected to complete your order.': 'I-redirect kamo aron makumpleto ang inyong order.',
            'Recent Orders': 'Bag-ong mga Order',
            'View All': 'Tan-awa ang Tanan',
            'No orders yet': 'Wala pay mga order',
            'Start shopping to see your orders here': 'Sugdi ang pagpalit aron makita ang inyong mga order dinhi',
            'Recommended for You': 'Girekomenda para Kanimo',
            'View More': 'Tan-awa Pa',
            'Quick Actions': 'Paspas nga mga Aksyon',
            'Browse': 'Tan-awa',
            'Track': 'Subayi',
            'Shop by Category': 'Palit pinaagi sa Kategorya',
            'Items in Cart': 'Mga Item sa Cart',
            'Spent': 'Nagasto',
            'Total Spent': 'Total nga Nagasto',
            'Browse our wide selection of fresh produce categories': 'Tan-awa ang among daghang pagpili sa mga kategorya sa preskong ani',
            'Why Choose Harvee?': 'Ngano Pilion ang Harvee?',
            'Experience the best way to buy fresh produce directly from local farmers': 'Masinati ang pinakamaayong paagi sa pagpalit og preskong ani direkta gikan sa lokal nga mga mag-uuma',
            '100% Fresh': '100% Presko',
            'Harvested at peak ripeness and delivered straight to you': 'Gi-ani sa hustong pagkahinog ug gi-deliver dayon kaninyo',
            'Support Local': 'Suporta sa Lokal',
            'Help local farmers thrive and strengthen your community': 'Tabangi ang lokal nga mga mag-uuma nga molambo ug mapalig-on ang inyong komunidad',
            'Best Prices': 'Labing Maayong Presyo',
            'No middlemen means better prices for everyone': 'Walay middlemen nagpasabot og mas maayong presyo para sa tanan',
            'Fast Delivery': 'Paspas nga Delivery',
            'Quick delivery while maintaining product freshness': 'Paspas nga delivery samtang gipadayon ang kapresko sa produkto',
            'Professional marketplace flow': 'Propesyonal nga dagan sa marketplace',
            'Guests can explore crops first, then sign in or sign up when they are ready to add items to cart and check out.': 'Ang mga bisita makasusi una sa mga ani, dayon mosulod o mag-sign up kung andam na sila modugang og mga item sa cart ug mo-checkout.',
            'Click, Farm, Enjoy!': 'Click, ani, lingaw!',
            'Cart is available for customer accounts': 'Ang cart magamit para sa customer accounts',
            'View Details': 'Tan-awa ang Detalye',
            'Connecting local farmers directly with customers for a fresher, fairer, farm-to-table experience.': 'Nagkonektar sa lokal nga mga mag-uuma diretso sa mga customer alang sa mas presko ug mas patas nga farm-to-table nga kasinatian.',
            'No products added yet': 'Wala pay naidugang nga mga produkto',
            'Add Your First Product': 'Idugang ang Imong Unang Produkto',
            'All products have sufficient stock': 'Igo ang stock sa tanang produkto',
            'Restock': 'Restock',
            'Pending Orders': 'Mga Pending nga Order',
            'Farmer Tips': 'Mga Tip sa Mag-uuma',
            'Keep your products updated with fresh photos': 'Hupti nga updated ang inyong mga produkto gamit ang bag-ong mga litrato',
            'Respond to customer inquiries quickly': 'Tubaga dayon ang mga pangutana sa customer',
            'Monitor low stock items to avoid missed sales': 'Bantayi ang mga item nga ubos ang stock aron malikayan ang napalpas nga halin',
            'Check pending orders daily': 'Susiha adlaw-adlaw ang mga pending nga order',
            'Need more help? Visit Farmer Support': 'Nanginahanglan pa og tabang? Bisitaha ang Farmer Support',
            'Empowering farmers to reach more customers with fresh, quality products.': 'Gihatagan og kusog ang mga mag-uuma aron makaabot sa mas daghang customer pinaagi sa presko ug dekalidad nga mga produkto.',
            'Quick Links': 'Paspas nga mga Link',
            'Support': 'Suporta',
            'Help Center': 'Help Center',
            'FAQ': 'FAQ',
            'Farming District, Philippines': 'Distrito sa Pag-uma, Pilipinas',
            'Growing together with our farmers.': 'Nagatubo nga magkuyog uban sa among mga mag-uuma.',
            'orders': 'mga order',
            'items': 'mga item',
            'in stock': 'naa sa stock',
            'left': 'nabilin'
        }
    };

    const originalTextNodes = new WeakMap();
    const originalAttributes = new WeakMap();
    let originalDocumentTitle = document.title;
    let activeLanguage = normalizeLanguage(loadStoredLanguage());
    let isApplyingTranslations = false;
    let scheduledTranslation = false;
    let observer = null;

    function normalizeLanguage(value) {
        const normalized = String(value || '').trim().toLowerCase();

        if (normalized === 'fil' || normalized === 'filipino' || normalized === 'tagalog' || normalized === 'tl') {
            return 'fil';
        }

        if (normalized === 'ceb' || normalized === 'bisaya' || normalized === 'cebuano') {
            return 'ceb';
        }

        return 'en';
    }

    function loadStoredLanguage() {
        const localValue = window.localStorage ? localStorage.getItem(STORAGE_KEY) : null;
        const cookieValue = readCookie(COOKIE_KEY);
        return localValue || cookieValue || config.defaultLanguage || 'en';
    }

    function readCookie(name) {
        const pattern = new RegExp('(?:^|; )' + name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '=([^;]*)');
        const match = document.cookie.match(pattern);
        return match ? decodeURIComponent(match[1]) : '';
    }

    function persistLanguage(language) {
        if (window.localStorage) {
            localStorage.setItem(STORAGE_KEY, language);
        }

        document.cookie = `${COOKIE_KEY}=${encodeURIComponent(language)}; path=/; max-age=31536000; SameSite=Lax`;
    }

    function htmlLanguage(language) {
        if (language === 'fil') {
            return 'tl';
        }

        if (language === 'ceb') {
            return 'ceb';
        }

        return 'en';
    }

    function normalizeWhitespace(value) {
        return String(value || '').replace(/\s+/g, ' ').trim();
    }

    function preserveOuterWhitespace(original, translated) {
        const match = String(original).match(/^(\s*)([\s\S]*?)(\s*)$/);
        if (!match) {
            return translated;
        }

        return `${match[1]}${translated}${match[3]}`;
    }

    function findExactTranslation(language, key) {
        const translations = EXACT_TRANSLATIONS[language];
        if (!translations) {
            return null;
        }

        if (Object.prototype.hasOwnProperty.call(translations, key)) {
            return translations[key];
        }

        const lowerKey = key.toLowerCase();
        const matchKey = Object.keys(translations).find((candidate) => candidate.toLowerCase() === lowerKey);
        return matchKey ? translations[matchKey] : null;
    }

    function translateFromOriginal(original, language) {
        if (language === 'en') {
            return original;
        }

        const normalized = normalizeWhitespace(original);
        if (!normalized) {
            return original;
        }

        const exact = findExactTranslation(language, normalized);
        if (exact) {
            return preserveOuterWhitespace(original, exact);
        }

        const regexRules = REGEX_TRANSLATIONS[language] || [];
        for (let index = 0; index < regexRules.length; index += 1) {
            const rule = regexRules[index];
            if (rule.pattern.test(normalized)) {
                const translated = normalized.replace(rule.pattern, rule.replace);
                return preserveOuterWhitespace(original, translated);
            }
        }

        const phraseRules = PHRASE_TRANSLATIONS[language];
        if (phraseRules) {
            let translated = String(original);
            const phrases = Object.keys(phraseRules).sort((left, right) => right.length - left.length);

            phrases.forEach((phrase) => {
                if (!phrase || translated.indexOf(phrase) === -1) {
                    return;
                }

                translated = translated.split(phrase).join(phraseRules[phrase]);
            });

            if (translated !== original) {
                return translated;
            }
        }

        return original;
    }

    function wrapDialogFunctions() {
        if (window.__harveeI18nDialogsWrapped) {
            return;
        }

        window.__harveeI18nDialogsWrapped = true;

        const nativeAlert = window.alert ? window.alert.bind(window) : null;
        const nativeConfirm = window.confirm ? window.confirm.bind(window) : null;

        if (nativeAlert) {
            window.alert = function (message) {
                return nativeAlert(translateFromOriginal(String(message), activeLanguage));
            };
        }

        if (nativeConfirm) {
            window.confirm = function (message) {
                return nativeConfirm(translateFromOriginal(String(message), activeLanguage));
            };
        }
    }

    function shouldTranslateTextNode(node) {
        if (!node || !node.parentElement) {
            return false;
        }

        if (!normalizeWhitespace(node.nodeValue || '')) {
            return false;
        }

        if (node.parentElement.closest('[data-i18n-skip="true"]')) {
            return false;
        }

        const tagName = node.parentElement.tagName;
        return !['SCRIPT', 'STYLE', 'NOSCRIPT', 'TEXTAREA'].includes(tagName);
    }

    function translateTextNode(node) {
        if (!shouldTranslateTextNode(node)) {
            return;
        }

        if (!originalTextNodes.has(node)) {
            originalTextNodes.set(node, node.nodeValue);
        }

        const original = originalTextNodes.get(node);
        const translated = translateFromOriginal(original, activeLanguage);

        if (node.nodeValue !== translated) {
            node.nodeValue = translated;
        }
    }

    function captureAttributes(element) {
        if (!originalAttributes.has(element)) {
            originalAttributes.set(element, {});
        }

        return originalAttributes.get(element);
    }

    function translateAttribute(element, attributeName) {
        if (!element.hasAttribute(attributeName)) {
            return;
        }

        const store = captureAttributes(element);
        if (!Object.prototype.hasOwnProperty.call(store, attributeName)) {
            store[attributeName] = element.getAttribute(attributeName);
        }

        const translated = translateFromOriginal(store[attributeName], activeLanguage);
        element.setAttribute(attributeName, translated);
    }

    function translateValueAttribute(element) {
        const type = (element.getAttribute('type') || '').toLowerCase();
        if (!['button', 'submit', 'reset'].includes(type) || !element.hasAttribute('value')) {
            return;
        }

        const store = captureAttributes(element);
        if (!Object.prototype.hasOwnProperty.call(store, 'value')) {
            store.value = element.getAttribute('value');
        }

        element.setAttribute('value', translateFromOriginal(store.value, activeLanguage));
    }

    function translateElementAttributes(root) {
        if (!(root instanceof Element)) {
            return;
        }

        const elements = [root].concat(Array.from(root.querySelectorAll('*')));
        elements.forEach((element) => {
            if (element.closest('[data-i18n-skip="true"]')) {
                return;
            }

            ['placeholder', 'title', 'aria-label', 'alt'].forEach((attributeName) => {
                translateAttribute(element, attributeName);
            });
            translateValueAttribute(element);
        });
    }

    function translateSubtree(root) {
        if (!root) {
            return;
        }

        if (root.nodeType === Node.TEXT_NODE) {
            translateTextNode(root);
            return;
        }

        if (!(root instanceof Element) && root !== document) {
            return;
        }

        translateElementAttributes(root === document ? document.documentElement : root);

        const walker = document.createTreeWalker(
            root === document ? document.body || document.documentElement : root,
            NodeFilter.SHOW_TEXT,
            null
        );

        let currentNode = walker.nextNode();
        while (currentNode) {
            translateTextNode(currentNode);
            currentNode = walker.nextNode();
        }
    }

    function syncSelectValue(select, language) {
        const normalizedValue = normalizeLanguage(select.value);
        if (normalizedValue !== language) {
            const option = Array.from(select.options).find((item) => normalizeLanguage(item.value || item.textContent) === language);
            if (option) {
                select.value = option.value;
            }
        }
    }

    function updateLanguageDock() {
        const dock = document.getElementById(DOCK_ID);
        const label = document.getElementById(LABEL_ID);
        const floatingSelect = document.getElementById(SELECTOR_ID);

        if (label) {
            label.textContent = translateFromOriginal('Language', activeLanguage).trim();
        }

        if (floatingSelect) {
            Array.from(floatingSelect.options).forEach((option) => {
                const language = normalizeLanguage(option.value);
                option.textContent = (config.labels && config.labels[language]) || LANGUAGE_LABELS[language];
            });
            syncSelectValue(floatingSelect, activeLanguage);
        }
    }

    function updateExistingSelectors() {
        const selectors = Array.from(document.querySelectorAll('#heroLanguageSelect, [data-language-select="true"]'));
        selectors.forEach((select) => {
            Array.from(select.options).forEach((option) => {
                const language = normalizeLanguage(option.value || option.textContent);
                const label = (config.labels && config.labels[language]) || LANGUAGE_LABELS[language] || option.textContent;
                option.textContent = label;
            });
            syncSelectValue(select, activeLanguage);
        });
    }

    function injectDockStyles() {
        if (document.getElementById('harveeLanguageDockStyles')) {
            return;
        }

        const style = document.createElement('style');
        style.id = 'harveeLanguageDockStyles';
        style.textContent = `
            #${DOCK_ID} {
                position: fixed;
                right: max(0.85rem, env(safe-area-inset-right));
                bottom: max(0.85rem, env(safe-area-inset-bottom));
                z-index: 9999;
                display: flex;
                align-items: center;
                gap: 0.55rem;
                padding: 0.52rem 0.62rem 0.52rem 0.55rem;
                border-radius: 999px;
                background: linear-gradient(135deg, rgba(255, 255, 255, 0.96), rgba(245, 251, 248, 0.98));
                box-shadow: 0 16px 34px -24px rgba(15, 23, 42, 0.45);
                border: 1px solid rgba(16, 133, 77, 0.18);
                backdrop-filter: blur(14px);
                font-family: inherit;
                max-width: calc(100vw - 1rem);
                min-height: 3.2rem;
            }
            #${DOCK_ID} .harvee-language-icon {
                width: 2rem;
                height: 2rem;
                border-radius: 999px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                background: linear-gradient(135deg, rgba(16, 133, 77, 0.12), rgba(5, 150, 105, 0.2));
                color: #10854d;
                flex-shrink: 0;
            }
            #${DOCK_ID} .harvee-language-icon svg {
                width: 1rem;
                height: 1rem;
                display: block;
            }
            #${DOCK_ID} .harvee-language-copy {
                display: flex;
                align-items: center;
                gap: 0.45rem;
                min-width: 0;
            }
            #${DOCK_ID} label {
                color: #1f2937;
                font-size: 0.76rem;
                font-weight: 700;
                white-space: nowrap;
                letter-spacing: 0.01em;
            }
            #${DOCK_ID} select {
                appearance: none;
                border: 1px solid rgba(16, 133, 77, 0.18);
                border-radius: 999px;
                background: #ffffff;
                color: #1f2937;
                font-size: 0.84rem;
                font-weight: 600;
                line-height: 1.2;
                padding: 0.52rem 2rem 0.52rem 0.82rem;
                outline: none;
                min-width: 6.6rem;
                max-width: 8.6rem;
                box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.5);
                transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
            }
            #${DOCK_ID} select:hover,
            #${DOCK_ID} select:focus {
                border-color: rgba(16, 133, 77, 0.32);
                box-shadow: 0 0 0 4px rgba(16, 133, 77, 0.08);
            }
            #${DOCK_ID}::after {
                content: "";
                position: absolute;
                right: 1.12rem;
                top: 50%;
                width: 0.42rem;
                height: 0.42rem;
                border-right: 1.5px solid #6b7280;
                border-bottom: 1.5px solid #6b7280;
                transform: translateY(-62%) rotate(45deg);
                pointer-events: none;
            }
            @media (max-width: 640px) {
                #${DOCK_ID} {
                    right: max(0.65rem, env(safe-area-inset-right));
                    bottom: max(0.65rem, env(safe-area-inset-bottom));
                    left: auto;
                    gap: 0.4rem;
                    padding: 0.45rem 0.5rem 0.45rem 0.45rem;
                    min-height: 2.9rem;
                }
                #${DOCK_ID} .harvee-language-icon {
                    width: 1.8rem;
                    height: 1.8rem;
                }
                #${DOCK_ID} label {
                    font-size: 0.72rem;
                }
                #${DOCK_ID} select {
                    font-size: 0.8rem;
                    min-width: 5.7rem;
                    max-width: 7.2rem;
                    padding: 0.45rem 1.75rem 0.45rem 0.7rem;
                }
                #${DOCK_ID}::after {
                    right: 0.98rem;
                }
            }
            @media (max-width: 480px) {
                #${DOCK_ID} {
                    padding: 0.38rem 0.42rem;
                    gap: 0.3rem;
                }
                #${DOCK_ID} .harvee-language-icon {
                    display: none;
                }
                #${DOCK_ID} select {
                    min-width: 5.8rem;
                    max-width: 6.8rem;
                }
                #${DOCK_ID} label {
                    display: none;
                }
                #${DOCK_ID}::after {
                    right: 0.9rem;
                }
            }
        `;
        document.head.appendChild(style);
    }

    function ensureLanguageSelector() {
        updateExistingSelectors();

        const hasPageSelector = document.querySelector('#heroLanguageSelect, [data-language-select="true"]');
        if (hasPageSelector) {
            updateLanguageDock();
            return;
        }

        injectDockStyles();

        if (!document.getElementById(DOCK_ID)) {
            const dock = document.createElement('div');
            dock.id = DOCK_ID;
            dock.setAttribute('data-i18n-skip', 'true');
            dock.innerHTML = `
                <span class="harvee-language-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 5h9"></path>
                        <path d="M7 3v2c0 4.418 2.239 8 5 8"></path>
                        <path d="M9 13c-2.761 0-5-3.582-5-8"></path>
                        <path d="M13 7h7"></path>
                        <path d="M17 5v2c0 4.418-2.239 8-5 8"></path>
                        <path d="M15 13c2.761 0 5-3.582 5-8"></path>
                        <path d="M8 17h8"></path>
                    </svg>
                </span>
                <div class="harvee-language-copy">
                    <label id="${LABEL_ID}" for="${SELECTOR_ID}">Language</label>
                    <select id="${SELECTOR_ID}">
                        <option value="en">${LANGUAGE_LABELS.en}</option>
                        <option value="fil">${LANGUAGE_LABELS.fil}</option>
                        <option value="ceb">${LANGUAGE_LABELS.ceb}</option>
                    </select>
                </div>
            `;
            document.body.appendChild(dock);
        }

        updateLanguageDock();
    }

    function bindSelectorEvents() {
        document.addEventListener('change', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLSelectElement)) {
                return;
            }

            if (target.id !== 'heroLanguageSelect' && target.id !== SELECTOR_ID && target.getAttribute('data-language-select') !== 'true') {
                return;
            }

            const selectedLanguage = normalizeLanguage(target.value || target.options[target.selectedIndex]?.textContent);
            applyLanguage(selectedLanguage);
        });
    }

    function scheduleTranslation() {
        if (scheduledTranslation) {
            return;
        }

        scheduledTranslation = true;
        window.requestAnimationFrame(() => {
            scheduledTranslation = false;
            translatePage();
        });
    }

    function observeMutations() {
        if (observer) {
            observer.disconnect();
        }

        observer = new MutationObserver((mutations) => {
            if (isApplyingTranslations) {
                return;
            }

            const needsRefresh = mutations.some((mutation) => mutation.type === 'childList' || mutation.type === 'characterData');
            if (needsRefresh) {
                scheduleTranslation();
            }
        });

        observer.observe(document.documentElement, {
            childList: true,
            characterData: true,
            subtree: true
        });
    }

    function translatePage() {
        isApplyingTranslations = true;
        document.documentElement.lang = htmlLanguage(activeLanguage);
        translateSubtree(document);
        document.title = translateFromOriginal(originalDocumentTitle, activeLanguage).trim();
        updateExistingSelectors();
        updateLanguageDock();
        isApplyingTranslations = false;
    }

    function applyLanguage(language) {
        activeLanguage = normalizeLanguage(language);
        persistLanguage(activeLanguage);
        translatePage();
    }

    function init() {
        originalDocumentTitle = document.title;
        persistLanguage(activeLanguage);
        wrapDialogFunctions();
        ensureLanguageSelector();
        bindSelectorEvents();
        translatePage();
        observeMutations();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
}());
