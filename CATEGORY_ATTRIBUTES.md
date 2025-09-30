# Category Attribute Image Switching

## Overview
This feature automatically changes the product images displayed on category archive pages based on selected attribute settings. For variable products, it shows the variation image that matches the configured attribute/term. Simple products are displayed normally since they don't have variations.

## How it works

### Admin Side (Backend)
1. Go to WooCommerce → Products → Categories
2. Edit any product category
3. You'll see a new section called "Category Attribute Filter"
4. Select ONE attribute using radio buttons (or "No attribute filter" to disable)
5. When you select an attribute, the system will load all available terms for that attribute
6. Select ONE specific term using radio buttons (or "None" to disable)
7. Save the category

### Frontend Behavior
- **Simple Products**: Display normally (no variations to process)
- **Variable Products**: 
  - If a variation exists with the selected attribute/term → Display that variation's image
  - If no matching variation exists → Display the default product image
- **No filtering applied**: All products are still shown in the category
- **Image switching**: Only the images change, not the product visibility

## Example Use Case
**Category**: "Black T-Shirts"
**Configuration**: 
- Select attribute "Color" with term "Black"

**Results**:
- **Simple products**: Show normally
- **Variable T-shirt with Black variation**: Shows the black variation image
- **Variable T-shirt without Black variation**: Shows default product image
- **All products remain visible** in the category

## Technical Implementation

### Files Modified:
- `admin/class-ibc-admin.php` - Added category attribute selection interface
- `admin/js/ibc-admin.js` - AJAX functionality for loading terms
- `admin/css/ibc-admin.css` - Styling for admin interface
- `public/class-ibc-public.php` - Automatic filtering logic

### Database Storage:
- Attribute selections are stored in the `category_attributes` meta field for each category
- Data structure:
```php
```php
array(
    'pa_color' => array(
        'enabled' => true,
        'terms' => array('black') // Single attribute with single term
    )
)
```

### Filtering Logic:
The system uses the `woocommerce_product_get_image_id` filter to modify product images:
- Intercepts image requests for variable products on category pages
- Matches variation attributes against category settings  
- Returns variation image ID if match found, otherwise original image

## Usage Example
1. Create a "Clothing" category
2. Select "Color" attribute with term "Red"
3. Save the category
4. Visit the category page:
   - Red t-shirt variations will show their red variation images
   - Products without red variations show default images
   - All products remain visible in the category
```

### Filtering Logic:
The system uses WordPress `pre_get_posts` hook to modify the main query on category pages:
- Adds `tax_query` parameters to filter products
- Uses AND logic between different attributes
- Each attribute can only have one selected term (radio button selection)

### Security Features:
- AJAX requests are protected with nonce verification
- All user inputs are sanitized and validated
- No frontend display means no security concerns with output

## CSS Classes for Admin Customization

### Admin Interface:
- `.ibc-attribute-wrapper` - Attribute checkbox wrapper
- `.ibc-terms-container` - Terms selection container
- `.ibc-terms-list` - Terms checkbox list

## Usage Example
1. Create a "Summer Clothing" category
2. Select "Season" attribute with term "Summer"
3. Select "Type" attribute with term "Shirts"
4. Save the category
5. Visit the category page - only products with Season=Summer AND Type=Shirts will be displayed