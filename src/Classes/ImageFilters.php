<?php

namespace Weboccult\EatcardCompanion\Classes;

use Exception;

/**
 * Class ImageFilters.
 *
 * @author Darshit Hedpara
 */
class ImageFilters
{
    /**
     * @usage :
     * \App\ImageFilters::applyFilter('FILTER_NAME','AWS-S3-FULL-URL-IMAGE');
     *
     * @available_filters :
     * [
     *  'productName',
     *  'productMedium',
     *  'userProfileDropDown',
     *  'storeBanner',
     * ]
     *
     * @description To use auto generated image scales from lambda function on AWS
     */
    public static array $filters = [
        'ProductNormalImage'       => ['size' => '200x200'],
        'UserProfileDropDownImage' => ['size' => '70x70'],
        'StoreBannerImage'         => ['size' => '940x220'],
        'StorePrintLogoImage'      => ['size' => '200xauto'],
        'EmailStoreLogo'           => ['size' => '200xauto'],
    ];

    /**
     * @param string $filter
     * @param string $s3BucketFullURL
     *
     * @throws Exception
     *
     * @return string
     */
    public static function applyFilter(string $filter, string $s3BucketFullURL): string
    {
        if (! isset(self::$filters[$filter])) {
            throw new Exception('Filter not supported', 422);
        }
        $dirname = pathinfo($s3BucketFullURL, PATHINFO_DIRNAME);

        return $dirname.'/'.self::$filters[$filter]['size'].'/'.basename($s3BucketFullURL);
    }
}
