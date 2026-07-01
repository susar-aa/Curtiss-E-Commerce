<?php
// ==========================================
// FORM SAFETY & FALLBACK ENGINE
// ==========================================
$db = new Database();

// Safely solve potential undefined variable warnings
$title = $data['title'] ?? 'Product Catalog Form';
$item = $data['item'] ?? null;

// Populate current variables (support edit mapping / create defaults)
$item_id = $item ? ($item->id ?? '') : '';
$item_code = $item ? ($item->item_code ?? '') : '';
$name = $item ? ($item->name ?? '') : '';
$selling_price = $item ? ($item->selling_price ?? '') : '';
$description = $item ? ($item->description ?? '') : '';

// Retrieve potential fallback features to prevent omissions
$barcode = $item ? ($item->barcode ?? '') : '';
$cost_price = $item ? ($item->cost_price ?? '') : '';
$wholesale_price = $item ? ($item->wholesale_price ?? '') : ''; // B2B Price field
$alert_qty = $item ? ($item->alert_qty ?? '5') : '5';
$category_id = $item ? ($item->category_id ?? '') : '';
$brand = $item ? ($item->brand ?? '') : '';
$unit = $item ? ($item->unit ?? 'pcs') : 'pcs';
$status = $item ? ($item->status ?? 'active') : 'active';
$image_path = $item ? ($item->image_path ?? '') : '';
$additional_images = $item ? ($item->additional_images ?? '[]') : '[]';
$additional_images_arr = [];
if (!empty($additional_images)) {
    $additional_images_arr = json_decode($additional_images, true) ?: [];
}
$weight = $item ? ($item->weight ?? '') : '';
$sample_code = $item ? ($item->sample_code ?? '') : '';

// Retrieve Relational Warehouse and Vendor variables
$warehouse_id = $item ? ($item->warehouse_id ?? '') : '';
$vendor_id = $item ? ($item->vendor_id ?? '') : '';
$retail_margin = $item ? ($item->retail_margin ?? '') : '';
$wholesale_margin = $item ? ($item->wholesale_margin ?? '') : '';

$is_edit = !empty($item_id);
$form_action = $is_edit ? APP_URL . '/inventory/edit/' . $item_id : APP_URL . '/inventory/add';

// Load categories, vendors and warehouses from controller
$categories = $data['categories'] ?? [];
$vendors = $data['vendors'] ?? [];
$warehouses = $data['warehouses'] ?? [];

// Fetch product attributes and their terms from local database
$synced_attributes = [];
$db_error_message = '';
try {
    $db->query("SELECT * FROM product_attributes ORDER BY name ASC");
    $synced_attributes = $db->resultSet() ?: [];
    foreach ($synced_attributes as $attr) {
        $db->query("SELECT * FROM product_attribute_terms WHERE attribute_id = :id ORDER BY name ASC");
        $db->bind(':id', $attr->id);
        $attr->terms = $db->resultSet() ?: [];
    }
} catch (Exception $e) {
    // Fail-safe empty array fallback
    $db_error_message = $e->getMessage();
}

if (!function_exists('erp_safe_utf8_convert')) {
    function erp_safe_utf8_convert($data) {
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $data[$k] = erp_safe_utf8_convert($v);
            }
        } else if (is_object($data)) {
            foreach ($data as $k => $v) {
                $data->$k = erp_safe_utf8_convert($v);
            }
        } else if (is_string($data)) {
            return mb_convert_encoding($data, 'UTF-8', 'UTF-8,ISO-8859-1,ASCII');
        }
        return $data;
    }
}

if (!function_exists('erp_safe_json_encode')) {
    function erp_safe_json_encode($data) {
        $json = json_encode($data);
        if ($json !== false) {
            return $json;
        }
        $clean_data = erp_safe_utf8_convert($data);
        return json_encode($clean_data) ?: '[]';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?> - Curtiss ERP</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Inter Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            /* Pure iOS System color variables (from index.php) */
            --c-bg: #f5f5f7;
            --c-surface: #ffffff;
            --c-surface-hover: #fafafa;
            --c-surface-active: #f0f0f0;
            --c-fill: #f5f5f7;
            --c-fill2: #e8e8ed;
            --c-separator: #d2d2d7;
            --c-blue: #0071e3;
            --c-blue-light: rgba(0, 113, 227, 0.08);
            --c-orange: #f56300;
            --c-orange-light: rgba(245, 99, 0, 0.08);
            --c-red: #ff3b30;
            --c-red-light: rgba(255, 59, 48, 0.08);
            --c-green: #34c759;
            --c-purple: #86308c;
            --c-purple-light: rgba(134, 48, 140, 0.08);
            
            --t-primary: #1d1d1f;
            --t-secondary: #6e6e73;
            --t-label: #86868b;
            --t-light: #ffffff;
            
            --r-sm: 8px;
            --r-md: 12px;
            --r-lg: 16px;
            --r-xl: 20px;
            
            --dur-fast: 0.2s;
            --dur-normal: 0.3s;
            --ease-ios: cubic-bezier(0.25, 0.8, 0.25, 1);
            --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            --font-mono: SFMono-Regular, Consolas, "Liberation Mono", Menlo, Courier, monospace;
        }

        body {
            background-color: var(--c-bg);
            color: var(--t-primary);
            font-family: var(--font-sans);
        }

        /* Clean, input aesthetics matching index.php */
        input[type="text"],
        input[type="number"],
        select,
        textarea {
            width: 100%;
            background: var(--c-surface);
            border: 0.5px solid var(--c-separator) !important;
            border-radius: var(--r-md) !important;
            color: var(--t-primary) !important;
            font-size: 13px !important;
            font-weight: 500 !important;
            padding: 10px 14px !important;
            outline: none !important;
            box-sizing: border-box !important;
            box-shadow: none !important;
            transition: border-color var(--dur-fast) var(--ease-ios),
                        box-shadow var(--dur-fast) var(--ease-ios);
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        select:focus,
        textarea:focus {
            border-color: var(--c-blue) !important;
            box-shadow: 0 0 0 3px var(--c-blue-light) !important;
        }

        /* Styling helper classes */
        label {
            font-size: 11px !important;
            font-weight: 600 !important;
            color: var(--t-secondary) !important;
            text-transform: uppercase !important;
            letter-spacing: 0.04em !important;
            margin-bottom: 6px !important;
            display: block !important;
        }

        .purple-lbl {
            color: var(--c-purple) !important;
        }

        .wholesale-input:focus {
            border-color: var(--c-purple) !important;
            box-shadow: 0 0 0 3px var(--c-purple-light) !important;
        }

        .empty-table-msg {
            text-align: center;
            color: var(--t-label);
            font-style: italic;
            padding: 32px !important;
        }

        /* Customize scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: var(--c-separator);
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: var(--t-label);
        }
    </style>
</head>
<body class="antialiased min-h-screen pb-24">

    <div class="max-w-[1500px] mx-auto px-6 py-6">
        
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <a href="<?php echo APP_URL; ?>/inventory" class="inline-flex items-center gap-2 text-slate-500 hover:text-black font-semibold text-sm transition-colors mb-2 group">
                    <i class="fa-solid fa-arrow-left transition-transform group-hover:-translate-x-1"></i> Back to Product Catalog
                </a>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900"><?php echo htmlspecialchars($title); ?></h1>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-white border border-slate-200 text-black flex items-center justify-center shadow-sm">
                <i class="fa-solid <?php echo $is_edit ? 'fa-pen-to-square' : 'fa-plus'; ?> text-lg"></i>
            </div>
        </div>

        <!-- Form Layout: Clean side-by-side without scrolling -->
        <form action="<?php echo $form_action; ?>" method="POST" id="productForm" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            
            <!-- Hidden inputs for variations and image compression base64 -->
            <input type="hidden" name="variations_json" id="variationsJson" value="[]">
            <input type="hidden" name="compressed_image_base64" id="compressedImageBase64" value="">
            <input type="hidden" name="image_deleted" id="imageDeleted" value="0">
            <input type="hidden" name="additional_images_base64" id="additionalImagesBase64" value="[]">
            <input type="hidden" name="existing_additional_images" id="existingAdditionalImages" value='<?php echo json_encode($additional_images_arr); ?>'>
            <!-- Hidden File Input for Variation Images -->
            <input type="file" id="varImageFileInput" accept="image/*" class="hidden">

            <!-- LEFT COLUMN (5/12 span): Media + Identifiers + Specs -->
            <div class="lg:col-span-5 space-y-6">
                
                <!-- Media Upload Card -->
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm space-y-4">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider flex items-center gap-2 border-b border-slate-100 pb-2">
                        <i class="fa-solid fa-image text-slate-500"></i> Product Media
                    </h3>
                    
                    <div id="dropzone" class="border-2 border-dashed border-slate-200 hover:border-black bg-slate-50/50 hover:bg-slate-50 rounded-xl p-4 transition-all duration-200 cursor-pointer text-center relative">
                        <input type="file" id="imageFileInput" accept="image/*" multiple class="absolute inset-0 opacity-0 cursor-pointer">
                        <div class="space-y-1">
                            <i class="fa-solid fa-cloud-arrow-up text-lg text-slate-450"></i>
                            <p class="text-xs font-semibold text-slate-700">Drag & drop or <span class="text-black underline">browse</span> to upload multiple images</p>
                            <p class="text-[10px] text-slate-400">First image in the grid below will be set as primary.</p>
                        </div>
                    </div>

                    <!-- Upload Progress -->
                    <div id="progressWrapper" class="bg-slate-100 rounded-full h-1.5 overflow-hidden hidden border border-slate-200">
                        <div id="progressBar" class="bg-black h-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                    <p id="progressText" class="text-[10px] text-slate-500 font-semibold mt-1 hidden"></p>

                    <!-- Main Product Images Grid -->
                    <div id="mainImagesGrid" class="grid grid-cols-4 gap-2 mt-2">
                        <!-- Populated dynamically by JS -->
                    </div>
                </div>

                <!-- Product Identifiers & Database Relations Card -->
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm space-y-4">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider flex items-center gap-2 border-b border-slate-100 pb-2">
                        <i class="fa-solid fa-barcode text-slate-500"></i> Core Info & Relations
                    </h3>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label>SKU / Code *</label>
                            <input type="text" name="item_code" id="mainItemCode" value="<?php echo htmlspecialchars($item_code); ?>" placeholder="SKU" required
                                   class="font-mono font-bold">
                        </div>
                        <div>
                            <label>Sample Code</label>
                            <input type="text" name="sample_code" id="mainSampleCode" value="<?php echo htmlspecialchars($sample_code ?? ''); ?>" placeholder="SMP-001"
                                   class="font-mono">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label>Barcode UPC/EAN</label>
                            <input type="text" name="barcode" id="mainBarcode" value="<?php echo htmlspecialchars($barcode); ?>" placeholder="Barcode"
                                   class="font-mono">
                        </div>
                        <div>
                            <label>Status</label>
                            <select name="status" id="mainStatusSelect" class="font-semibold cursor-pointer" required>
                                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label>Item Name / Product Title *</label>
                        <input type="text" name="name" id="mainProductName" value="<?php echo htmlspecialchars($name); ?>" placeholder="Falcon Luxury Pen" required
                               class="font-semibold" spellcheck="true" list="englishProductWords">
                        <datalist id="englishProductWords">
                            <option value="Pen"></option>
                            <option value="Pencil"></option>
                            <option value="Paper"></option>
                            <option value="Notebook"></option>
                            <option value="File"></option>
                            <option value="Folder"></option>
                            <option value="Marker"></option>
                            <option value="Eraser"></option>
                            <option value="Ruler"></option>
                            <option value="Tape"></option>
                            <option value="Glue"></option>
                            <option value="Scissors"></option>
                            <option value="Stapler"></option>
                            <option value="Organizer"></option>
                            <option value="Holder"></option>
                            <option value="Desk"></option>
                            <option value="Office"></option>
                            <option value="School"></option>
                            <option value="Highlighter"></option>
                            <option value="Crayon"></option>
                            <option value="Watercolor"></option>
                            <option value="Canvas"></option>
                            <option value="Brush"></option>
                            <option value="Sketchbook"></option>
                            <option value="Ink"></option>
                            <option value="Cartridge"></option>
                            <option value="Calculator"></option>
                            <option value="Board"></option>
                            <option value="Chalk"></option>
                            <option value="Card"></option>
                            <option value="Envelope"></option>
                            <option value="Label"></option>
                            <option value="Box"></option>
                            <option value="Pack"></option>
                            <option value="Set"></option>
                            <option value="Black"></option>
                            <option value="Blue"></option>
                            <option value="Red"></option>
                            <option value="Green"></option>
                            <option value="White"></option>
                            <option value="Yellow"></option>
                            <option value="Metal"></option>
                            <option value="Plastic"></option>
                            <option value="Wooden"></option>
                            <option value="Leather"></option>
                            <option value="Clear"></option>
                            <option value="Gel"></option>
                            <option value="Ballpoint"></option>
                            <option value="Fountain"></option>
                            <option value="Rollerball"></option>
                            <option value="Mechanical"></option>
                            <option value="A4"></option>
                            <option value="A3"></option>
                            <option value="Premium"></option>
                            <option value="Luxury"></option>
                            <option value="Standard"></option>
                            <option value="Executive"></option>
                            <option value="Classic"></option>
                            <option value="Deluxe"></option>
                            <option value="Pro"></option>
                            <option value="Ultra"></option>
                            <option value="Super"></option>
                            <option value="Pocket"></option>
                            <option value="Desktop"></option>
                            <option value="Portable"></option>
                            <option value="Heavy"></option>
                            <option value="Duty"></option>
                            <option value="Double"></option>
                            <option value="Single"></option>
                            <option value="Color"></option>
                            <option value="Coloured"></option>
                            <option value="Sheet"></option>
                            <option value="Book"></option>
                            <option value="Binder"></option>
                            <option value="Clips"></option>
                            <option value="Pins"></option>
                            <option value="Rubber"></option>
                            <option value="Band"></option>
                            <option value="Sharpener"></option>
                            <option value="Cutter"></option>
                            <option value="Whiteboard"></option>
                            <option value="Magnetic"></option>
                        </datalist>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label>Category</label>
                            <select name="category_id" id="mainCategorySelect" class="font-semibold cursor-pointer" required>
                                <option value="">General Stationery</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat->id; ?>" <?php echo $category_id == $cat->id ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Brand / Manufacturer</label>
                            <input type="text" name="brand" value="<?php echo htmlspecialchars($brand); ?>" placeholder="Brand">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label>Supplier / Vendor</label>
                            <select name="vendor_id" id="mainVendorSelect" class="font-semibold cursor-pointer" required>
                                <option value="">Select Supplier</option>
                                <?php foreach ($vendors as $v): ?>
                                    <option value="<?php echo $v->id; ?>" <?php echo $vendor_id == $v->id ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($v->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Warehouse storage bin</label>
                            <select name="warehouse_id" id="mainWarehouseSelect" class="font-semibold cursor-pointer" required>
                                <option value="">Select Warehouse</option>
                                <?php foreach ($warehouses as $wh): ?>
                                    <option value="<?php echo $wh->id; ?>" <?php echo $warehouse_id == $wh->id ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($wh->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm space-y-4">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider flex items-center gap-2 border-b border-slate-100 pb-2">
                        <i class="fa-solid fa-scale-balanced text-slate-500"></i> Specs & alert limits
                    </h3>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label>Alert Qty</label>
                            <input type="number" name="alert_qty" id="mainAlertQty" value="<?php echo htmlspecialchars($alert_qty); ?>" placeholder="5"
                                   class="font-mono" required>
                        </div>
                        <div>
                            <label>UOM</label>
                            <select name="unit" class="font-semibold cursor-pointer">
                                <option value="pcs" <?php echo $unit === 'pcs' ? 'selected' : ''; ?>>pcs</option>
                                <option value="pack" <?php echo $unit === 'pack' ? 'selected' : ''; ?>>pack</option>
                                <option value="box" <?php echo $unit === 'box' ? 'selected' : ''; ?>>box</option>
                                <option value="kg" <?php echo $unit === 'kg' ? 'selected' : ''; ?>>kg</option>
                            </select>
                        </div>
                        <div>
                            <label>Weight</label>
                            <input type="text" name="weight" value="<?php echo htmlspecialchars($weight); ?>" placeholder="e.g. 150g">
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN (7/12 span): Pricing + Variations + Notes -->
            <div class="lg:col-span-7 space-y-6">
                
                <!-- Pricing Card -->
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm space-y-4" id="basePricingContainer">
                    <div id="basePricingAlert" class="bg-purple-50 border border-purple-200 text-purple-955 p-3.5 rounded-xl text-xs flex items-center gap-3 hidden">
                        <i class="fa-solid fa-lock text-purple-650 text-sm animate-bounce"></i>
                        <div>
                            <strong>Pricing Deactivated:</strong> Pricing is managed at the variation level below.
                        </div>
                    </div>
                    
                    <div id="basePricingSection" class="space-y-4">
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider flex items-center gap-2 border-b border-slate-100 pb-2">
                            <i class="fa-solid fa-wallet text-slate-500"></i> Base Pricing & Profit Margins
                        </h3>
                        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                            <div>
                                <label>Cost (LKR)</label>
                                <input type="number" step="0.01" name="cost_price" id="costPriceInput" value="<?php echo htmlspecialchars($cost_price); ?>" oninput="calculateMarkupProfit()" placeholder="0.00"
                                       class="font-mono font-bold" required>
                            </div>
                            <div>
                                <label>Retail Marg. %</label>
                                <input type="number" step="0.1" name="retail_margin" id="retailMarginInput" value="<?php echo htmlspecialchars($retail_margin); ?>" oninput="calculatePriceFromMargin('retail')" placeholder="0.0"
                                       class="font-mono font-bold" required>
                            </div>
                            <div>
                                <label>Retail Price *</label>
                                <input type="number" step="0.01" name="selling_price" id="sellingPriceInput" value="<?php echo htmlspecialchars($selling_price); ?>" oninput="calculateMarginFromPrice('retail')" placeholder="0.00" required
                                       class="font-mono font-bold">
                            </div>
                            <div>
                                <label class="purple-lbl">B2B Margin %</label>
                                <input type="number" step="0.1" name="wholesale_margin" id="wholesaleMarginInput" value="<?php echo htmlspecialchars($wholesale_margin); ?>" oninput="calculatePriceFromMargin('wholesale')" placeholder="0.0"
                                       class="font-mono font-bold wholesale-input" required>
                            </div>
                            <div>
                                <label class="purple-lbl">B2B Price *</label>
                                <input type="number" step="0.01" name="wholesale_price" id="wholesalePriceInput" value="<?php echo htmlspecialchars($wholesale_price); ?>" oninput="calculateMarginFromPrice('wholesale')" placeholder="0.00"
                                       class="font-mono font-bold wholesale-input" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attributes & Variations Card -->
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm space-y-4">
                    <div class="flex justify-between items-center border-b border-slate-100 pb-2">
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider flex items-center gap-2">
                            <i class="fa-solid fa-diagram-project text-slate-500"></i> Attributes & Variations
                        </h3>
                        <span class="text-[10px] text-slate-400 italic">No stock inputs required (Managed via transactions)</span>
                    </div>

                    <!-- Builder shelf -->
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-150 space-y-3">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                            <div>
                                <label>1. Select Attribute</label>
                                <select id="attrGroupSelect" onchange="handleAttributeSelectionChange()" class="font-semibold cursor-pointer">
                                    <option value="">-- Choose Attribute --</option>
                                    <?php foreach ($synced_attributes as $attr): ?>
                                        <option value="<?php echo $attr->id; ?>">
                                            <?php echo htmlspecialchars($attr->name); ?> (pa_<?php echo htmlspecialchars($attr->slug); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="Custom">Custom Group...</option>
                                </select>
                            </div>

                            <div id="customGroupWrapper" class="hidden">
                                <label>Group Name</label>
                                <input type="text" id="customGroupName" placeholder="Style, Size">
                            </div>

                            <div id="customValuesWrapper" class="hidden md:col-span-2">
                                <label>Press Enter to add options</label>
                                <input type="text" id="customValuesInput" placeholder="Type and hit Enter">
                            </div>

                            <div id="syncedTermsWrapper" class="md:col-span-2">
                                <label>2. Add Term</label>
                                <div id="syncedTermsContainer" class="flex flex-wrap gap-1.5 bg-white border border-slate-200 rounded-xl p-2 min-h-[38px] items-center text-slate-400 italic text-[11px]">
                                    Select attribute to show terms.
                                </div>
                            </div>
                        </div>
                        <div id="attributeTagsContainer" class="flex flex-wrap gap-1.5 pt-1 hidden"></div>
                    </div>

                    <!-- Variations table list -->
                    <div class="border border-slate-200 rounded-xl overflow-hidden bg-slate-50 max-h-[300px] overflow-y-auto shadow-inner">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-slate-100 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider text-[10px]">
                                    <th class="py-2.5 px-3 w-[15%]">Option</th>
                                    <th class="py-2.5 px-3 w-[10%]">Image</th>
                                    <th class="py-2.5 px-3 w-[15%]">SKU *</th>
                                    <th class="py-2.5 px-3 w-[10%] text-right">Cost</th>
                                    <th class="py-2.5 px-3 w-[10%] text-right">Retail Marg.</th>
                                    <th class="py-2.5 px-3 w-[12%] text-right">Retail Price</th>
                                    <th class="py-2.5 px-3 w-[10%] text-right">B2B Marg.</th>
                                    <th class="py-2.5 px-3 w-[12%] text-right">B2B Price</th>
                                    <th class="py-2.5 px-3 w-[6%] text-right"></th>
                                </tr>
                            </thead>
                            <tbody id="variationsTableBody" class="divide-y divide-slate-200">
                                <tr id="noVariationsRow">
                                    <td colspan="9" class="py-6 text-center text-slate-400 empty-table-msg">No variations created. Simple standard product.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Notes specifications description -->
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm space-y-4">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider flex items-center gap-2 border-b border-slate-100 pb-2">
                        <i class="fa-solid fa-file-lines text-slate-500"></i> Notes & Specifications
                    </h3>
                    <textarea name="description" rows="3" placeholder="Enter specifications notes..."
                              class="leading-relaxed"><?php echo htmlspecialchars($description); ?></textarea>
                </div>

                <!-- Facebook Autopost Option (Only visible when adding new product) -->
                <?php if (!$is_edit): ?>
                <div class="p-4 bg-blue-50/40 border border-blue-150 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5">
                        <input type="checkbox" name="share_facebook" id="shareFacebook" value="1" 
                               class="w-4 h-4 text-blue-600 border-slate-300 rounded cursor-pointer">
                        <label for="shareFacebook" class="text-xs font-semibold text-slate-700 flex items-center gap-2 select-none cursor-pointer">
                            <i class="fa-brands fa-facebook text-blue-600 text-base"></i> Autopost new product to Facebook Page
                        </label>
                    </div>
                    <?php if (empty($data['settings']->facebook_page_id) || empty($data['settings']->facebook_access_token)): ?>
                        <span class="text-[10px] text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-lg font-semibold flex items-center gap-1">
                            <i class="fa-solid fa-triangle-exclamation"></i> API keys not configured.
                        </span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Floating Actions Footer Bar -->
    <div class="fixed bottom-0 left-0 right-0 bg-white/80 backdrop-blur-md border-t border-slate-200 py-3.5 px-6 flex justify-end gap-3 z-50 shadow-lg">
        <a href="<?php echo APP_URL; ?>/inventory" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition-all">
            Cancel
        </a>
        <button type="submit" form="productForm" class="px-6 py-2.5 bg-black hover:bg-zinc-800 text-white text-xs font-bold rounded-xl shadow-sm transition-all flex items-center gap-1.5 cursor-pointer">
            <i class="fa-solid fa-save"></i> Save Product Entry
        </button>
    </div>

    <!-- Client side dynamic compressor, calculators, variations serializer scripts -->
    <script>
        // Product Attributes & Terms data set
        const syncedAttributes = <?php echo erp_safe_json_encode($synced_attributes); ?>;
        const dbErrorMessage = <?php echo json_encode($db_error_message); ?>;
        const jsonErrorMessage = <?php echo json_encode(json_last_error() !== JSON_ERROR_NONE ? json_last_error_msg() : ''); ?>;
        if (dbErrorMessage) {
            console.error("Database Error loading synced attributes/terms:", dbErrorMessage);
        }
        if (jsonErrorMessage) {
            console.error("JSON Encoding Error in syncedAttributes:", jsonErrorMessage);
        }

        // Variations Tracker State
        let variations = [];
        let activeCustomTags = [];

        function calculatePriceFromMargin(type) {
            const costVal = parseFloat(document.getElementById('costPriceInput').value) || 0;
            if (type === 'retail') {
                const marginVal = parseFloat(document.getElementById('retailMarginInput').value) || 0;
                if (costVal > 0) {
                    const price = costVal + (costVal * marginVal / 100);
                    document.getElementById('sellingPriceInput').value = price.toFixed(2);
                }
            } else if (type === 'wholesale') {
                const marginVal = parseFloat(document.getElementById('wholesaleMarginInput').value) || 0;
                if (costVal > 0) {
                    const price = costVal + (costVal * marginVal / 100);
                    document.getElementById('wholesalePriceInput').value = price.toFixed(2);
                }
            }
        }

        function calculateMarginFromPrice(type) {
            const costVal = parseFloat(document.getElementById('costPriceInput').value) || 0;
            if (type === 'retail') {
                const priceVal = parseFloat(document.getElementById('sellingPriceInput').value) || 0;
                if (costVal > 0) {
                    const margin = ((priceVal - costVal) / costVal) * 100;
                    document.getElementById('retailMarginInput').value = margin.toFixed(1);
                }
            } else if (type === 'wholesale') {
                const priceVal = parseFloat(document.getElementById('wholesalePriceInput').value) || 0;
                if (costVal > 0) {
                    const margin = ((priceVal - costVal) / costVal) * 100;
                    document.getElementById('wholesaleMarginInput').value = margin.toFixed(1);
                }
            }
        }

        function calculateMarkupProfit() {
            const costVal = parseFloat(document.getElementById('costPriceInput').value) || 0;
            const retailMarginVal = parseFloat(document.getElementById('retailMarginInput').value) || 0;
            const wholesaleMarginVal = parseFloat(document.getElementById('wholesaleMarginInput').value) || 0;

            if (costVal > 0) {
                if (retailMarginVal > 0) {
                    calculatePriceFromMargin('retail');
                } else {
                    calculateMarginFromPrice('retail');
                }

                if (wholesaleMarginVal > 0) {
                    calculatePriceFromMargin('wholesale');
                } else {
                    calculateMarginFromPrice('wholesale');
                }
            }
        }

        /**
         * Disable/Hide base prices when variations exist (Variations have different prices)
         */
        function toggleBasePricingSection() {
            const pricingSection = document.getElementById('basePricingSection');
            const pricingAlert = document.getElementById('basePricingAlert');
            const costInput = document.getElementById('costPriceInput');
            const sellInput = document.getElementById('sellingPriceInput');
            const wholesaleInput = document.getElementById('wholesalePriceInput');

            // Count valid variation rows
            const hasVariations = variations.filter(v => v !== null).length > 0;

            if (hasVariations) {
                pricingSection.classList.add('opacity-45', 'pointer-events-none');
                pricingAlert.classList.remove('hidden');
                
                // Remove HTML5 constraint required flags during active variation syncing
                costInput.removeAttribute('required');
                sellInput.removeAttribute('required');
                wholesaleInput.removeAttribute('required');
            } else {
                pricingSection.classList.remove('opacity-45', 'pointer-events-none');
                pricingAlert.classList.add('hidden');
                
                costInput.setAttribute('required', 'required');
                sellInput.setAttribute('required', 'required');
                wholesaleInput.setAttribute('required', 'required');
            }
        }

        // ==========================================
        // CLIENT-SIDE MULTI-IMAGE GALLERY SYSTEM
        // ==========================================
        let mainImages = []; // Each: { id, src, type: 'existing'|'new', path: '...' }

        // Initialize with PHP values
        const phpPrimaryImage = <?php echo json_encode($image_path); ?>;
        const phpAdditionalImages = <?php echo json_encode($additional_images_arr); ?>;

        if (phpPrimaryImage) {
            mainImages.push({
                id: 'primary_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5),
                src: '<?php echo APP_URL; ?>/' + phpPrimaryImage.replace(/^public\//, ''),
                type: 'existing',
                path: phpPrimaryImage
            });
        }
        phpAdditionalImages.forEach(path => {
            mainImages.push({
                id: 'existing_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5),
                src: '<?php echo APP_URL; ?>/' + path.replace(/^public\//, ''),
                type: 'existing',
                path: path
            });
        });

        // Function to update the hidden inputs based on current mainImages state
        function updateMainImagesInputs() {
            if (mainImages.length > 0) {
                const primary = mainImages[0];
                if (primary.type === 'new') {
                    document.getElementById('compressedImageBase64').value = primary.src;
                    document.getElementById('imageDeleted').value = '0';
                } else {
                    document.getElementById('compressedImageBase64').value = '';
                    document.getElementById('imageDeleted').value = '0';
                }
            } else {
                document.getElementById('compressedImageBase64').value = '';
                document.getElementById('imageDeleted').value = '1';
            }

            // Additional images (remaining existing paths)
            const remainingExisting = mainImages.slice(1)
                .filter(img => img.type === 'existing')
                .map(img => img.path);
            document.getElementById('existingAdditionalImages').value = JSON.stringify(remainingExisting);

            // Additional images (newly uploaded base64 strings)
            const newAdditional = mainImages.slice(1)
                .filter(img => img.type === 'new')
                .map(img => img.src);
            document.getElementById('additionalImagesBase64').value = JSON.stringify(newAdditional);
        }

        // Render main product images grid
        function renderMainImagesGrid() {
            const grid = document.getElementById('mainImagesGrid');
            if (!grid) return;
            grid.innerHTML = '';

            mainImages.forEach((img, idx) => {
                const wrapper = document.createElement('div');
                wrapper.className = "relative w-full aspect-square rounded-xl border border-slate-200 bg-slate-50 flex items-center justify-center overflow-hidden group shadow-sm";
                
                const isPrimary = (idx === 0);
                
                wrapper.innerHTML = `
                    <img src="${img.src}" class="object-cover w-full h-full">
                    ${isPrimary ? `<span class="absolute top-1 left-1 px-1.5 py-0.5 text-[8px] font-bold text-white bg-black/75 rounded-md uppercase tracking-wider">Primary</span>` : ''}
                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-1.5">
                        ${!isPrimary ? `<button type="button" onclick="setAsPrimary(${idx})" title="Set as primary" class="p-1.5 bg-white hover:bg-black hover:text-white text-slate-800 rounded-lg shadow transition-all text-[9px] cursor-pointer"><i class="fa-solid fa-star"></i></button>` : ''}
                        <button type="button" onclick="removeMainImage(${idx})" title="Delete image" class="p-1.5 bg-red-650 hover:bg-red-700 text-white rounded-lg shadow transition-all text-[9px] cursor-pointer"><i class="fa-solid fa-trash-can"></i></button>
                    </div>
                `;
                grid.appendChild(wrapper);
            });

            updateMainImagesInputs();
        }

        window.setAsPrimary = function(index) {
            if (index > 0 && index < mainImages.length) {
                const img = mainImages.splice(index, 1)[0];
                mainImages.unshift(img);
                renderMainImagesGrid();
            }
        };

        window.removeMainImage = function(index) {
            mainImages.splice(index, 1);
            renderMainImagesGrid();
        };

        const dropzone = document.getElementById('dropzone');
        const fileInput = document.getElementById('imageFileInput');
        const progressWrapper = document.getElementById('progressWrapper');
        const progressBar = document.getElementById('progressBar');
        const progressText = document.getElementById('progressText');

        if (dropzone) {
            ['dragenter', 'dragover'].forEach(eventName => {
                dropzone.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    dropzone.classList.add('border-black', 'bg-slate-100');
                }, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropzone.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    dropzone.classList.remove('border-black', 'bg-slate-100');
                }, false);
            });

            dropzone.addEventListener('drop', (e) => {
                const files = e.dataTransfer.files;
                if (files.length) handleMultipleImagesUpload(files);
            });
        }

        if (fileInput) {
            fileInput.addEventListener('change', (e) => {
                if (e.target.files.length) handleMultipleImagesUpload(e.target.files);
            });
        }

        async function handleMultipleImagesUpload(files) {
            if (!progressWrapper || !progressBar || !progressText) return;
            progressWrapper.classList.remove('hidden');
            progressText.classList.remove('hidden');
            
            const totalFiles = files.length;
            for (let i = 0; i < totalFiles; i++) {
                const file = files[i];
                if (!file.type.match('image.*')) {
                    alert(`Skipped non-image file: ${file.name}`);
                    continue;
                }
                
                progressBar.style.width = '10%';
                progressText.innerText = `Processing image ${i + 1} of ${totalFiles}...`;
                
                try {
                    const base64 = await compressImageAsync(file);
                    mainImages.push({
                        id: 'new_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5),
                        src: base64,
                        type: 'new'
                    });
                } catch (err) {
                    console.error("Compression failed", err);
                }
            }
            
            progressBar.style.width = '100%';
            progressText.innerText = "All images processed successfully!";
            renderMainImagesGrid();
            
            setTimeout(() => {
                progressWrapper.classList.add('hidden');
                progressText.classList.add('hidden');
            }, 1000);
            
            if (fileInput) fileInput.value = '';
        }

        function compressImageAsync(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const img = new Image();
                    img.src = event.target.result;
                    img.onload = function() {
                        const canvas = document.createElement('canvas');
                        let width = img.width;
                        let height = img.height;
                        const max_size = 1600;
                        if (width > height) {
                            if (width > max_size) {
                                height *= max_size / width;
                                width = max_size;
                            }
                        } else {
                            if (height > max_size) {
                                width *= max_size / height;
                                height = max_size;
                            }
                        }
                        canvas.width = width;
                        canvas.height = height;
                        const ctx = canvas.getContext('2d');
                        ctx.imageSmoothingEnabled = true;
                        ctx.imageSmoothingQuality = 'high';
                        ctx.drawImage(img, 0, 0, width, height);
                        const compressedBase64 = canvas.toDataURL('image/jpeg', 0.85);
                        resolve(compressedBase64);
                    };
                    img.onerror = () => reject("Image load error");
                };
                reader.onerror = () => reject("File read error");
                reader.readAsDataURL(file);
            });
        }

        // Render initially
        renderMainImagesGrid();

        // ==========================================
        // DYNAMIC SYNCED ATTRIBUTES & VARIATIONS
        // ==========================================
        const selectGroup = document.getElementById('attrGroupSelect');
        const customGroupWrapper = document.getElementById('customGroupWrapper');
        const customValuesWrapper = document.getElementById('customValuesWrapper');
        const customValuesInput = document.getElementById('customValuesInput');
        const syncedTermsWrapper = document.getElementById('syncedTermsWrapper');
        const syncedTermsContainer = document.getElementById('syncedTermsContainer');
        const tagsContainer = document.getElementById('attributeTagsContainer');
        const tableBody = document.getElementById('variationsTableBody');
        const noVariationsRow = document.getElementById('noVariationsRow');

        /**
         * Handle Synced Attribute Selection Changes
         */
        function handleAttributeSelectionChange() {
            const val = selectGroup.value;
            console.log("=== handleAttributeSelectionChange ===");
            console.log("Selected attribute ID value:", val, "Type:", typeof val);
            console.log("syncedAttributes array:", syncedAttributes);

            // Reset layouts
            customGroupWrapper.classList.add('hidden');
            customValuesWrapper.classList.add('hidden');
            tagsContainer.classList.add('hidden');
            syncedTermsWrapper.classList.add('hidden');
            syncedTermsContainer.innerHTML = '';
            activeCustomTags = [];

            if (val === '') {
                syncedTermsWrapper.classList.remove('hidden');
                syncedTermsContainer.innerHTML = 'Select a synced attribute on the left to show available terms list.';
            } else if (val === 'Custom') {
                customGroupWrapper.classList.remove('hidden');
                customValuesWrapper.classList.remove('hidden');
                tagsContainer.classList.remove('hidden');
                renderCustomTags();
            } else {
                syncedTermsWrapper.classList.remove('hidden');
                
                // Fetch attribute terms from matched local synced array
                const attrObj = syncedAttributes.find(a => String(a.id) === String(val));
                console.log("Matched Attribute Object (attrObj):", attrObj);
                if (attrObj) {
                    console.log("Matched Attribute terms:", attrObj.terms);
                } else {
                    console.warn("Could not find matching attribute in syncedAttributes for ID:", val);
                }

                if (attrObj && attrObj.terms && attrObj.terms.length > 0) {
                    attrObj.terms.forEach(term => {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = "px-2.5 py-1 bg-slate-100 hover:bg-black hover:text-white text-slate-800 text-[11px] font-bold rounded-lg border border-slate-200 transition-all flex items-center gap-1 cursor-pointer";
                        btn.innerHTML = `<i class="fa-solid fa-plus-circle text-[10px]"></i> Add ${escapeHtml(term.name)}`;
                        btn.onclick = () => {
                            addVariationRow({ attribute: term.name });
                        };
                        syncedTermsContainer.appendChild(btn);
                    });
                } else {
                    syncedTermsContainer.innerHTML = '<span class="text-amber-600 font-semibold"><i class="fa-solid fa-circle-exclamation mr-1"></i> No terms exist for this attribute. Please sync attributes or add a Custom Attribute group.</span>';
                }
            }
        }

        if (selectGroup) {
            selectGroup.addEventListener('change', handleAttributeSelectionChange);
        }

        // Listener for custom tags entry
        customValuesInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                const rawVal = customValuesInput.value.replace(',', '').trim();
                if (rawVal !== '' && !activeCustomTags.includes(rawVal)) {
                    activeCustomTags.push(rawVal);
                    renderCustomTags();
                    customValuesInput.value = '';
                }
            }
        });

        /**
         * Renders custom clickable attributes values as tags
         */
        function renderCustomTags() {
            tagsContainer.innerHTML = '';
            activeCustomTags.forEach(tag => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = "px-2.5 py-1 bg-slate-100 hover:bg-black hover:text-white text-slate-800 text-[11px] font-bold rounded-lg border border-slate-200 transition-all flex items-center gap-1 cursor-pointer";
                btn.innerHTML = `<i class="fa-solid fa-plus-circle text-[10px]"></i> Add <strong>${escapeHtml(tag)}</strong>`;
                btn.onclick = () => {
                    addVariationRow({ attribute: tag });
                };
                tagsContainer.appendChild(btn);
            });
        }

        function updateNoVariationsVisibility() {
            const activeCount = variations.filter(v => v !== null).length;
            if (activeCount === 0) {
                noVariationsRow.classList.remove('hidden');
            } else {
                noVariationsRow.classList.add('hidden');
            }
        }

        function calculateVarPriceFromMargin(index, type) {
            const costInput = document.getElementById(`var-cost-${index}`);
            const marginInput = document.getElementById(`var-${type}-margin-${index}`);
            const priceInput = document.getElementById(`var-${type}-price-${index}`);

            const costVal = parseFloat(costInput.value) || 0;
            const marginVal = parseFloat(marginInput.value) || 0;

            if (costVal > 0) {
                const price = costVal + (costVal * marginVal / 100);
                priceInput.value = price.toFixed(2);
                updateVariationValue(index, type === 'retail' ? 'price' : 'wholesale_price', priceInput.value);
            }
        }

        function calculateVarMarginFromPrice(index, type) {
            const costInput = document.getElementById(`var-cost-${index}`);
            const marginInput = document.getElementById(`var-${type}-margin-${index}`);
            const priceInput = document.getElementById(`var-${type}-price-${index}`);

            const costVal = parseFloat(costInput.value) || 0;
            const priceVal = parseFloat(priceInput.value) || 0;

            if (costVal > 0) {
                const margin = ((priceVal - costVal) / costVal) * 100;
                marginInput.value = margin.toFixed(1);
                updateVariationValue(index, type === 'retail' ? 'retail_margin' : 'wholesale_margin', marginInput.value);
            }
        }

        function calculateVarRowMaster(index) {
            const retailMarginInput = document.getElementById(`var-retail-margin-${index}`);
            const wholesaleMarginInput = document.getElementById(`var-wholesale-margin-${index}`);

            const retailMarginVal = parseFloat(retailMarginInput.value) || 0;
            const wholesaleMarginVal = parseFloat(wholesaleMarginInput.value) || 0;

            if (retailMarginVal > 0) {
                calculateVarPriceFromMargin(index, 'retail');
            } else {
                calculateVarMarginFromPrice(index, 'retail');
            }

            if (wholesaleMarginVal > 0) {
                calculateVarPriceFromMargin(index, 'wholesale');
            } else {
                calculateVarMarginFromPrice(index, 'wholesale');
            }
        }

        window.getVarImageSrc = function(rowData) {
            if (rowData.image_base64) {
                return rowData.image_base64;
            }
            if (rowData.image_path) {
                return '<?php echo APP_URL; ?>/' + rowData.image_path.replace(/^public\//, '');
            }
            return 'https://placehold.co/100?text=No+Img';
        };

        let currentVarUploadIndex = null;
        window.triggerVarImageUpload = function(index) {
            currentVarUploadIndex = index;
            document.getElementById('varImageFileInput').click();
        };

        document.addEventListener('DOMContentLoaded', () => {
            const varFileInput = document.getElementById('varImageFileInput');
            if (varFileInput) {
                varFileInput.addEventListener('change', async (e) => {
                    if (e.target.files.length && currentVarUploadIndex !== null) {
                        const file = e.target.files[0];
                        if (!file.type.match('image.*')) {
                            alert('Oops! Only image files (PNG, JPG, JPEG) are accepted.');
                            return;
                        }
                        try {
                            const base64 = await compressImageAsync(file);
                            updateVariationValue(currentVarUploadIndex, 'image_base64', base64);
                            refreshVarImageCell(currentVarUploadIndex);
                        } catch (err) {
                            console.error("Variation image compression failed", err);
                        }
                    }
                    varFileInput.value = '';
                });
            }
        });

        function refreshVarImageCell(index) {
            const rowData = variations[index];
            if (!rowData) return;
            const row = document.getElementById(`variation-row-${index}`);
            if (!row) return;
            const cells = row.getElementsByTagName('td');
            if (cells.length > 1) {
                cells[1].innerHTML = `
                    <div class="relative w-8 h-8 rounded-lg border border-slate-250 bg-white flex items-center justify-center overflow-hidden mx-auto group shadow-sm">
                        <img id="var-img-preview-${index}" src="${getVarImageSrc(rowData)}" class="object-cover w-full h-full">
                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-1 cursor-pointer" onclick="triggerVarImageUpload(${index})">
                            <i class="fa-solid fa-camera text-[10px] text-white"></i>
                        </div>
                        ${(rowData.image_path || rowData.image_base64) ? `
                        <button type="button" onclick="event.stopPropagation(); removeVarImage(${index});" class="absolute -top-0.5 -right-0.5 p-0.5 bg-red-650 hover:bg-red-750 text-white rounded-full transition-all text-[8px] leading-none shadow flex items-center justify-center w-3 h-3 cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
                        ` : ''}
                    </div>
                `;
            }
        }

        window.removeVarImage = function(index) {
            if (variations[index]) {
                variations[index].image_path = '';
                variations[index].image_base64 = '';
                refreshVarImageCell(index);
                serializeVariations();
            }
        };

        /**
         * Appends an interactive variation item row
         */
        function addVariationRow(existing = null) {
            const index = variations.length;
            const mainSku = document.getElementById('mainItemCode').value || 'SKU';
            const defaultCostPrice = document.getElementById('costPriceInput').value || '0.00';
            const defaultSellPrice = document.getElementById('sellingPriceInput').value || '0.00';
            const defaultWholePrice = document.getElementById('wholesalePriceInput').value || '0.00';

            const rowData = {
                id: existing ? (existing.id || '') : '',
                attribute: existing ? (existing.attribute || '') : '',
                sku: existing ? (existing.sku || `${mainSku}-${index + 1}`) : `${mainSku}-${index + 1}`,
                cost_price: existing ? (existing.cost_price || defaultCostPrice) : defaultCostPrice,
                price: existing ? (existing.price || defaultSellPrice) : defaultSellPrice,
                wholesale_price: existing ? (existing.wholesale_price || defaultWholePrice) : defaultWholePrice,
                retail_margin: existing ? (existing.retail_margin || '') : '',
                wholesale_margin: existing ? (existing.wholesale_margin || '') : '',
                image_path: existing ? (existing.image_path || '') : '',
                image_base64: ''
            };

            // Calculate default margins if empty
            const costVal = parseFloat(rowData.cost_price) || 0;
            if (costVal > 0) {
                if (!rowData.retail_margin && parseFloat(rowData.price) > 0) {
                    rowData.retail_margin = (((parseFloat(rowData.price) - costVal) / costVal) * 100).toFixed(1);
                }
                if (!rowData.wholesale_margin && parseFloat(rowData.wholesale_price) > 0) {
                    rowData.wholesale_margin = (((parseFloat(rowData.wholesale_price) - costVal) / costVal) * 100).toFixed(1);
                }
            }

            variations.push(rowData);

            const row = document.createElement('tr');
            row.id = `variation-row-${index}`;
            row.className = "hover:bg-slate-100/50 transition-colors";
            row.innerHTML = `
                <td class="py-2.5 px-3 font-bold text-slate-800">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-805 border border-slate-200">
                        ${escapeHtml(rowData.attribute)}
                    </span>
                    <input type="hidden" value="${escapeHtml(rowData.attribute)}" oninput="updateVariationValue(${index}, 'attribute', this.value)">
                </td>
                <td class="py-2 px-2 text-center">
                    <div class="relative w-8 h-8 rounded-lg border border-slate-250 bg-white flex items-center justify-center overflow-hidden mx-auto group shadow-sm">
                        <img id="var-img-preview-${index}" src="${getVarImageSrc(rowData)}" class="object-cover w-full h-full">
                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-1 cursor-pointer" onclick="triggerVarImageUpload(${index})">
                            <i class="fa-solid fa-camera text-[10px] text-white"></i>
                        </div>
                        ${(rowData.image_path || rowData.image_base64) ? `
                        <button type="button" onclick="event.stopPropagation(); removeVarImage(${index});" class="absolute -top-0.5 -right-0.5 p-0.5 bg-red-650 hover:bg-red-750 text-white rounded-full transition-all text-[8px] leading-none shadow flex items-center justify-center w-3 h-3 cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
                        ` : ''}
                    </div>
                </td>
                <td class="py-2 px-2">
                    <input type="text" value="${escapeHtml(rowData.sku)}" oninput="updateVariationValue(${index}, 'sku', this.value)" placeholder="SKU" class="w-full bg-white border border-slate-200 rounded-lg px-2 py-1 text-xs font-mono focus:border-black focus:ring-0 focus:outline-none">
                </td>
                <td class="py-2 px-2 text-right">
                    <input type="number" step="0.01" id="var-cost-${index}" value="${rowData.cost_price}" oninput="updateVariationValue(${index}, 'cost_price', this.value); calculateVarRowMaster(${index});" placeholder="0.00" class="w-full bg-white border border-slate-200 rounded-lg px-2 py-1 text-xs font-mono text-right focus:border-black focus:ring-0 focus:outline-none">
                </td>
                <td class="py-2 px-2 text-right">
                    <input type="number" step="0.1" id="var-retail-margin-${index}" value="${rowData.retail_margin}" oninput="updateVariationValue(${index}, 'retail_margin', this.value); calculateVarPriceFromMargin(${index}, 'retail');" placeholder="0.0" class="w-full bg-white border border-slate-200 rounded-lg px-2 py-1 text-xs font-mono text-right focus:border-black focus:ring-0 focus:outline-none">
                </td>
                <td class="py-2 px-2 text-right">
                    <input type="number" step="0.01" id="var-retail-price-${index}" value="${rowData.price}" oninput="updateVariationValue(${index}, 'price', this.value); calculateVarMarginFromPrice(${index}, 'retail');" placeholder="0.00" class="w-full bg-white border border-slate-200 rounded-lg px-2 py-1 text-xs font-mono text-right focus:border-black focus:ring-0 focus:outline-none">
                </td>
                <td class="py-2 px-2 text-right">
                    <input type="number" step="0.1" id="var-wholesale-margin-${index}" value="${rowData.wholesale_margin}" oninput="updateVariationValue(${index}, 'wholesale_margin', this.value); calculateVarPriceFromMargin(${index}, 'wholesale');" placeholder="0.0" class="w-full bg-purple-50/20 border border-purple-200 rounded-lg px-2 py-1 text-xs font-mono text-right text-purple-900 focus:border-purple-500 focus:ring-0 focus:outline-none font-bold font-bold">
                </td>
                <td class="py-2 px-2 text-right">
                    <input type="number" step="0.01" id="var-wholesale-price-${index}" value="${rowData.wholesale_price}" oninput="updateVariationValue(${index}, 'wholesale_price', this.value); calculateVarMarginFromPrice(${index}, 'wholesale');" placeholder="0.00" class="w-full bg-purple-50/20 border border-purple-200 rounded-lg px-2 py-1 text-xs font-mono text-right text-purple-900 focus:border-purple-500 focus:ring-0 focus:outline-none font-bold">
                </td>
                <td class="py-2 px-2 text-right">
                    <button type="button" onclick="removeVariationRow(${index})" class="p-1 bg-red-50 hover:bg-red-100 text-red-650 border border-red-100 rounded-lg transition-colors cursor-pointer text-xs"><i class="fa-solid fa-trash-can"></i></button>
                </td>
            `;

            tableBody.appendChild(row);
            updateNoVariationsVisibility();
            toggleBasePricingSection();
            serializeVariations();
        }

        function updateVariationValue(index, field, value) {
            if (variations[index]) {
                variations[index][field] = value;
                serializeVariations();
            }
        }

        function removeVariationRow(index) {
            const row = document.getElementById(`variation-row-${index}`);
            if (row) row.remove();
            
            variations[index] = null; // Mark as deleted to keep indexing consistent
            serializeVariations();
            
            const activeVariations = variations.filter(v => v !== null);
            if (activeVariations.length === 0) {
                variations = [];
                updateNoVariationsVisibility();
            }
            toggleBasePricingSection();
        }

        function serializeVariations() {
            const filtered = variations.filter(v => v !== null);
            document.getElementById('variationsJson').value = JSON.stringify(filtered);
        }

        function escapeHtml(str) {
            if (str === null || str === undefined) return '';
            str = String(str);
            return str
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // ==========================================
        // INITIALIZATIONS
        // ==========================================
        window.addEventListener('DOMContentLoaded', () => {
            const isEdit = <?php echo $is_edit ? 'true' : 'false'; ?>;
            const sellingPriceLoaded = parseFloat(document.getElementById('sellingPriceInput').value) || 0;
            const wholesalePriceLoaded = parseFloat(document.getElementById('wholesalePriceInput').value) || 0;

            if (isEdit && (sellingPriceLoaded > 0 || wholesalePriceLoaded > 0)) {
                // In edit mode, we preserve the actual saved prices and recalculate margins to match
                if (sellingPriceLoaded > 0) {
                    calculateMarginFromPrice('retail');
                }
                if (wholesalePriceLoaded > 0) {
                    calculateMarginFromPrice('wholesale');
                }
            } else {
                calculateMarkupProfit();
            }

            <?php if ($is_edit && isset($item->variations_json) && !empty($item->variations_json)): ?>
                const savedVariations = <?php echo json_encode(json_decode($item->variations_json) ?: [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
                if (Array.isArray(savedVariations)) {
                    savedVariations.forEach(item => addVariationRow(item));
                }
            <?php endif; ?>
        });

        // ==========================================
        // DYNAMIC UNIQUE VALIDATION & CODE GENERATOR
        // ==========================================
        const itemId = <?php echo json_encode($item_id ? intval($item_id) : 0); ?>;
        const mainCategorySelect = document.getElementById('mainCategorySelect');
        const mainSampleCode = document.getElementById('mainSampleCode');
        const mainItemCode = document.getElementById('mainItemCode');

        let skuHasDuplicate = false;
        let sampleHasDuplicate = false;

        const mainProductName = document.getElementById('mainProductName');
        
        // Auto-capitalize every word's first letter as they type (without cursor jumping)
        if (mainProductName) {
            mainProductName.addEventListener('input', function() {
                const start = this.selectionStart;
                const end = this.selectionEnd;
                const originalValue = this.value;
                const capitalizedValue = originalValue.replace(/\b\w/g, char => char.toUpperCase());
                if (originalValue !== capitalizedValue) {
                    this.value = capitalizedValue;
                    this.setSelectionRange(start, end);
                }
            });
            mainProductName.addEventListener('blur', function() {
                this.value = this.value.replace(/\b\w/g, char => char.toUpperCase());
            });
        }

        const requiredInputs = [
            { el: document.getElementById('mainProductName'), isSelect: false, label: 'Item Name' },
            { el: document.getElementById('mainItemCode'), isSelect: false, label: 'SKU' },
            { el: document.getElementById('mainAlertQty'), isSelect: false, label: 'Alert Qty' },
            { el: document.getElementById('costPriceInput'), isSelect: false, label: 'Cost' },
            { el: document.getElementById('retailMarginInput'), isSelect: false, label: 'Retail Margin' },
            { el: document.getElementById('sellingPriceInput'), isSelect: false, label: 'Retail Price' },
            { el: document.getElementById('wholesaleMarginInput'), isSelect: false, label: 'B2B Margin' },
            { el: document.getElementById('wholesalePriceInput'), isSelect: false, label: 'B2B Price' },
            { el: document.getElementById('mainCategorySelect'), isSelect: true, label: 'Category' },
            { el: document.getElementById('mainVendorSelect'), isSelect: true, label: 'Supplier' },
            { el: document.getElementById('mainWarehouseSelect'), isSelect: true, label: 'Warehouse' },
            { el: document.getElementById('mainStatusSelect'), isSelect: true, label: 'Status' }
        ];

        function validateField(element, isSelect = false) {
            if (!element) return true;
            
            // If it's a base pricing field and variations exist, ignore validation
            const hasVariations = (typeof variations !== 'undefined') && variations.filter(v => v !== null).length > 0;
            if (hasVariations && ['costPriceInput', 'retailMarginInput', 'sellingPriceInput', 'wholesaleMarginInput', 'wholesalePriceInput'].includes(element.id)) {
                clearFieldError(element, isSelect);
                return true;
            }

            const val = element.value.trim();
            if (val === '') {
                showFieldError(element, isSelect);
                return false;
            } else {
                clearFieldError(element, isSelect);
                return true;
            }
        }

        function showFieldError(element, isSelect) {
            if (isSelect) {
                const wrapper = element.closest('.searchable-select-wrapper') || element.nextElementSibling;
                if (wrapper) {
                    const inputEl = wrapper.querySelector('.searchable-select-input');
                    if (inputEl) {
                        inputEl.classList.remove('border-slate-200');
                        inputEl.classList.add('border-rose-500', 'focus:ring-rose-500/20', 'focus:border-rose-500');
                    }
                }
            } else {
                element.classList.remove('border-slate-200');
                element.classList.add('border-rose-500', 'focus:ring-rose-500/20', 'focus:border-rose-500');
            }
        }

        function clearFieldError(element, isSelect) {
            if (isSelect) {
                const wrapper = element.closest('.searchable-select-wrapper') || element.nextElementSibling;
                if (wrapper) {
                    const inputEl = wrapper.querySelector('.searchable-select-input');
                    if (inputEl) {
                        inputEl.classList.remove('border-rose-500', 'focus:ring-rose-500/20', 'focus:border-rose-500');
                        inputEl.classList.add('border-slate-200');
                    }
                }
            } else {
                element.classList.remove('border-rose-500', 'focus:ring-rose-500/20', 'focus:border-rose-500');
                element.classList.add('border-slate-200');
            }
        }

        requiredInputs.forEach(item => {
            if (item.el) {
                const eventType = item.isSelect ? 'change' : 'input';
                item.el.addEventListener(eventType, () => {
                    validateField(item.el, item.isSelect);
                });
            }
        });

        // Form submission parameters mapping & validation
        document.getElementById('productForm').addEventListener('submit', (e) => {
            serializeVariations();
            
            let missingFields = [];
            requiredInputs.forEach(item => {
                if (item.el && !validateField(item.el, item.isSelect)) {
                    missingFields.push(item.label);
                }
            });

            if (missingFields.length > 0) {
                e.preventDefault();
                alert("Please fill in all required fields:\n" + missingFields.map(f => "- " + f).join("\n"));
                const overlay = document.getElementById('saveLoadingOverlay');
                if (overlay) overlay.style.display = 'none';
                
                // Scroll to the first invalid field
                const firstInvalid = requiredInputs.find(item => {
                    if (!item.el) return false;
                    const hasVariations = (typeof variations !== 'undefined') && variations.filter(v => v !== null).length > 0;
                    if (hasVariations && ['costPriceInput', 'retailMarginInput', 'sellingPriceInput', 'wholesaleMarginInput', 'wholesalePriceInput'].includes(item.el.id)) {
                        return false;
                    }
                    return !item.el.value.trim();
                });
                if (firstInvalid && firstInvalid.el) {
                    const scrollTarget = firstInvalid.isSelect ? 
                        (firstInvalid.el.closest('.searchable-select-wrapper') || firstInvalid.el) : 
                        firstInvalid.el;
                    scrollTarget.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    if (!firstInvalid.isSelect) {
                        firstInvalid.el.focus();
                    }
                }
                return;
            }

            if (skuHasDuplicate || sampleHasDuplicate) {
                e.preventDefault();
                let errMsg = "Please resolve duplicate conflicts before saving:\n";
                if (skuHasDuplicate) errMsg += "- SKU / Code is already in use.\n";
                if (sampleHasDuplicate) errMsg += "- Sample Code is already in use.\n";
                alert(errMsg);
                const overlay = document.getElementById('saveLoadingOverlay');
                if (overlay) overlay.style.display = 'none';
            }
        });

        if (mainCategorySelect) {
            mainCategorySelect.addEventListener('change', function() {
                const categoryId = this.value;
                if (!categoryId) {
                    if (mainSampleCode) {
                        mainSampleCode.value = '';
                        clearSampleError();
                    }
                    return;
                }

                fetch('<?php echo APP_URL; ?>/inventory/generateSampleCode?category_id=' + categoryId + '&item_id=' + itemId)
                    .then(res => res.json())
                    .then(data => {
                        if (data && data.sample_code && mainSampleCode) {
                            mainSampleCode.value = data.sample_code;
                            checkDuplicates();
                        }
                    })
                    .catch(err => console.error('Error generating sample code:', err));
            });
        }

        let debounceTimer;
        function debouncedCheck() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(checkDuplicates, 300);
        }

        if (mainItemCode) mainItemCode.addEventListener('input', debouncedCheck);
        if (mainSampleCode) mainSampleCode.addEventListener('input', debouncedCheck);

        function checkDuplicates() {
            const skuVal = mainItemCode ? mainItemCode.value.trim() : '';
            const sampleVal = mainSampleCode ? mainSampleCode.value.trim() : '';

            if (skuVal === '' && sampleVal === '') {
                clearSkuError();
                clearSampleError();
                return;
            }

            fetch('<?php echo APP_URL; ?>/inventory/checkDuplicates?item_code=' + encodeURIComponent(skuVal) + '&sample_code=' + encodeURIComponent(sampleVal) + '&item_id=' + itemId)
                .then(res => res.json())
                .then(data => {
                    if (data.sku_exists) {
                        skuHasDuplicate = true;
                        showSkuError("SKU is already in use by '" + data.sku_owner + "'.");
                    } else {
                        skuHasDuplicate = false;
                        clearSkuError();
                    }

                    if (data.sample_exists) {
                        sampleHasDuplicate = true;
                        showSampleError("Sample Code is already in use by '" + data.sample_owner + "'.");
                    } else {
                        sampleHasDuplicate = false;
                        clearSampleError();
                    }
                })
                .catch(err => console.error('Error checking duplicates:', err));
        }

        function showSkuError(msg) {
            if (!mainItemCode) return;
            mainItemCode.classList.remove('border-slate-200');
            mainItemCode.classList.add('border-rose-500', 'focus:ring-rose-500/20', 'focus:border-rose-500');
            let errEl = document.getElementById('skuDuplicateError');
            if (!errEl) {
                errEl = document.createElement('p');
                errEl.id = 'skuDuplicateError';
                errEl.className = 'text-xs text-rose-500 font-semibold mt-1';
                mainItemCode.parentNode.appendChild(errEl);
            }
            errEl.textContent = msg;
        }

        function clearSkuError() {
            if (mainItemCode) {
                mainItemCode.classList.remove('border-rose-500', 'focus:ring-rose-500/20', 'focus:border-rose-500');
                mainItemCode.classList.add('border-slate-200');
            }
            const errEl = document.getElementById('skuDuplicateError');
            if (errEl) errEl.remove();
        }

        function showSampleError(msg) {
            if (!mainSampleCode) return;
            mainSampleCode.classList.remove('border-slate-200');
            mainSampleCode.classList.add('border-rose-500', 'focus:ring-rose-500/20', 'focus:border-rose-500');
            let errEl = document.getElementById('sampleDuplicateError');
            if (!errEl) {
                errEl = document.createElement('p');
                errEl.id = 'sampleDuplicateError';
                errEl.className = 'text-xs text-rose-500 font-semibold mt-1';
                mainSampleCode.parentNode.appendChild(errEl);
            }
            errEl.textContent = msg;
        }

        function clearSampleError() {
            if (mainSampleCode) {
                mainSampleCode.classList.remove('border-rose-500', 'focus:ring-rose-500/20', 'focus:border-rose-500');
                mainSampleCode.classList.add('border-slate-200');
            }
            const errEl = document.getElementById('sampleDuplicateError');
            if (errEl) errEl.remove();
        }

        // Run initial duplicate check on load to catch pre-existing duplicate inputs if any
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(checkDuplicates, 500);
        });
    </script>
<?php include '../app/Views/layouts/resilient_loader.php'; ?>
</body>
</html>