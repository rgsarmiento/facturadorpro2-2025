<?php

if (!function_exists('get_logo_base64')) {
    /**
     * Get company logo as base64 data URI
     * 
     * @param string $logoFilename The logo filename
     * @return string|null Base64 data URI or null if file doesn't exist
     */
    function get_logo_base64($logoFilename)
    {
        if (empty($logoFilename)) {
            return null;
        }

        $logoPath = public_path("storage/uploads/logos/{$logoFilename}");
        
        if (!file_exists($logoPath)) {
            return null;
        }

        try {
            $mimeType = mime_content_type($logoPath);
            $base64 = base64_encode(file_get_contents($logoPath));
            return "data:{$mimeType};base64, {$base64}";
        } catch (\Exception $e) {
            \Log::error("Error loading logo: {$logoFilename}", ['error' => $e->getMessage()]);
            return null;
        }
    }
}

if (!function_exists('render_company_logo')) {
    /**
     * Render company logo img tag
     * 
     * @param object $company Company object with logo and name properties
     * @param string $class CSS class for the img tag
     * @param string $style Inline style for the img tag
     * @return string HTML img tag or empty string if logo doesn't exist
     */
    function render_company_logo($company, $class = 'company_logo', $style = 'max-width: 150px;')
    {
        if (empty($company->logo)) {
            return '';
        }

        $logoDataUri = get_logo_base64($company->logo);
        
        if ($logoDataUri === null) {
            return '';
        }

        return '<img src="' . $logoDataUri . '" alt="' . htmlspecialchars($company->name) . '" class="' . $class . '" style="' . $style . '">';
    }
}
