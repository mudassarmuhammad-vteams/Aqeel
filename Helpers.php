<?php

class Helpers
{

    public static function getUserProfileThumb($image, $width = null, $height = null, $folder = null)
    {

        if ($folder == null)
            $folder = 'blogauthors';
        if ($width == null)
            $width = 100;
        if ($height == null)
            $height = 100;
        /*check if the thumbnail already exists*/
        if (!file_exists(realPath(Yii::app()->basePath . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'cache') . DIRECTORY_SEPARATOR . $image)) {
            /*check if not the first time or the thumbnail is not present / uploaded */
            if ($image != '' && file_exists(realPath(Yii::app()->basePath . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $folder) . DIRECTORY_SEPARATOR . $image)) {

                /*Create path for thumbnail base directory*/
                $path_for_thumb = realPath(Yii::app()->basePath . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'cache');

                /*Create thumbnail name*/
                $name_for_thumb = $image;

                /*Thumb path and name concatinated to complete the path */
                $path_for_thumb = $path_for_thumb . DIRECTORY_SEPARATOR . $name_for_thumb;
                /*Create the path*/
                Yii::app()->ThumbsGen->createThumb(realPath(Yii::app()->basePath . DIRECTORY_SEPARATOR . '..') . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $image, $path_for_thumb, $width, $height);
                return $name_for_thumb;
            } else {
                return $name_for_thumb = 'no-image.jpg';
            }
        } else {
            /*Get existing thumbnail name*/
            return $name_for_thumb = $image;
        }
    }

    public static function IfWinesInCart()
    {
        Yii::import('application.modules.store.models.*');
        $exist = false;
        if (Store::getCartContent() != array()) {
            foreach (Store::getCartContent() as $key => $val) {
                if ($val['product_type'] == "W") {
                    $exist = true;
                }
            }
        }
        if (SessionHelper::getSession('manual_order_id') !== NULL) {
            $order_id = SessionHelper::getSession('manual_order_id');
            $orItems = OrderItems::model()->findAll('order_id = :order_id', array(':order_id' => $order_id));

            foreach ($orItems as $item) {
                if ($item->product_type == "W") {
                    $exist = true;
                }
            }
        }
        return $exist;
    }

    /*
     * Helpers::is_shipping_available($state_id)
     */
    public static function is_shipping_available($state_id)
    {
        Yii::import('application.modules.wineryprofile.models.*');
        $exist = Helpers::IfWinesInCart();
        $allowed = false;
        if ($exist) {
            $settings = StateProfile::model()->find('state_id=:state_id', array(':state_id' => $state_id));
            if ($settings) {
                $allowed = ($settings->compliance_check == 1) ? true : false;
            }
        } else {
            $allowed = true;
        }
        return $allowed;
    }

    /*
     * Helpers::get_compliance_level()
     */
    public static function get_compliance_level()
    {
        Yii::import('application.modules.wineryprofile.models.*');
        $level = Yii::app()->db->createCommand()
            ->select('level')
            ->from('compliance_level')
            ->queryRow();
        return isset($level['level']) ? $level['level'] : "Basic";
    }

    public static function is_valid_zipcode($zip_code)
    {
        Yii::import('application.modules.wineryprofile.models.*');
        $zip = ZipTaxDetail::model()->find('zip_code=:zip_code', array(':zip_code' => $zip_code));
        $allowed = true;

        if (!$zip) {
            $allowed = false;
            Yii::import('ext.zip-tax.*');
            // Set the API key
            $apiKey = '9X696HB';
            // Instantiate the client
            $zipTax = new ZipTax($apiKey);
            $rate = $zipTax->request($zip_code);
            if (count($rate->results) > 0) {
                $zipCode = new ZipCodes;
                foreach ($rate->results as $res) {
                    $state = States::model()->find('abbreviation=:abbreviation', array(':abbreviation' => $res->geoState));
                    /*====Zip code data====*/
                    $zipCode->zip_code = $zip_code;
                    $zipCode->state = $state->id;
                    $zipCode->county = $res->geoCounty;
                    $zipCode->added_date = date('Y-m-d');
                    /*=========TaxDetail=============*/
                    $tax = new ZipTaxDetail;
                    $tax->zip_code = $zip_code;
                    $tax->city = $res->geoCity;
                    $tax->county = $res->geoCounty;
                    $tax->state = $state->id;
                    $tax->sales_tax = $res->taxSales;
                    $tax->use_tax = $res->taxUse;
                    $tax->state_sales_tax = $res->stateSalesTax;
                    $tax->state_use_tax = $res->stateUseTax;
                    $tax->city_sales_tax = $res->citySalesTax;
                    $tax->city_use_tax = $res->cityUseTax;
                    $tax->county_sales_tax = $res->countySalesTax;
                    $tax->county_use_tax = $res->countyUseTax;
                    $tax->district_sales_tax = $res->districtSalesTax;
                    $tax->district_use_tax = $res->districtUseTax;
                    $tax->city_tax_code = $res->cityTaxCode;
                    $tax->county_tax_code = $res->countyTaxCode;
                    $tax->service_taxable = $res->txbService;
                    $tax->freight_taxable = $res->txbFreight;
                    $tax->added_date = date('Y-m-d');
                    $tax->save(false);
                } //results
                $zipCode->save(false);
                $allowed = self::is_valid_zipcode($zip_code); // call same function to check if now it is exist in the system
            }
        }
        return $allowed;
    }

    /*
     * This function will return county, cities and state for given zip code
     * $zip_code numerical US zipcode
     */
    public static function get_address_by_zipcode($zip_code)
    {
        Yii::import('application.modules.tax_rates.models.*');
        $zip_codes = array();
        if (is_numeric($zip_code)) {
            $address = ZipTaxDetail::model()->findAll('zip_code=:zip_code', array(':zip_code' => $zip_code));
            if ($address) {
                foreach ($address as $addr) {
                    $zip_codes['county'] = $addr->county;
                    $zip_codes['state'] = $addr->state;
                    $zip_codes['cities'][] = $addr->city;
                }
            }
        }
        return $zip_codes;
    }

    /*
     * This function will return county, cities and state for given zip code
     * $zip_code numerical US zipcode
     */
    public static function get_state_by_zipcode($zip_code)
    {
        Yii::import('application.modules.tax_rates.models.*');
        $state_id = 0;
        if (is_numeric($zip_code)) {
            $address = ZipCodes::model()->find('zip_code=:zip_code', array(':zip_code' => $zip_code));
            if ($address) {
                $state_id = $address->state;
            }
        }
        return $state_id;
    }

    public static function get_address_by_city($city)
    {
        Yii::import('application.modules.tax_rates.models.*');
        $return = array();

        $address = ZipTaxDetail::model()->find('city=:city', array(':city' => $city));
        if ($address) {
            $return[$address->city] = array('county' => $address->county, 'state' => $address->state, 'city' => $address->city);

        }

        return $return;
    }

    /*
     * This function will return tax rates for given zip code in form of associative array
     * It will first check for the tax rate in system, but if not found it in DB it will call
     * zip-tax API to import tax rates and again return associative array of results
     * @Param: $zip_code 
     * @Return: array of taxes in a zip code
     */
    public static function get_tax_by_zipcode($zip_code)
    {
        Yii::import('application.modules.tax_rates.models.*');
        $zip_codes = array();


        $taxes = ZipTaxDetail::model()->findAll('zip_code=:zip_code', array(':zip_code' => $zip_code));
        if ($taxes) {
            foreach ($taxes as $addr) {
                $zip_codes[$addr->zip_code][] = array('county' => $addr->county, 'state' => $addr->state,
                    'city' => $addr->city, 'sales_tax' => $addr->sales_tax, 'use_tax' => $addr->use_tax);
                $zip_codes[$addr->zip_code][$addr->city] = array('county' => $addr->county, 'state' => $addr->state,
                    'city' => $addr->city, 'sales_tax' => $addr->sales_tax, 'use_tax' => $addr->use_tax);
            }
        } else {
            Yii::import('ext.zip-tax.*');
            // Set the API key
            $apiKey = '9X696HB';
            // Instantiate the client
            $zipTax = new ZipTax($apiKey);
            $rate = $zipTax->request($zip_code);
            if (count($rate->results) > 0) {
                foreach ($rate->results as $res) {
                    $state = States::model()->find('abbreviation=:abbreviation', array(':abbreviation' => $res->geoState));

                    $tax = new ZipTaxDetail;
                    $tax->zip_code = $zip_code;
                    $tax->city = $res->geoCity;
                    $tax->county = $res->geoCounty;
                    $tax->state = $state->id;
                    $tax->sales_tax = $res->taxSales;
                    $tax->use_tax = $res->taxUse;
                    $tax->state_sales_tax = $res->stateSalesTax;
                    $tax->state_use_tax = $res->stateUseTax;
                    $tax->city_sales_tax = $res->citySalesTax;
                    $tax->city_use_tax = $res->cityUseTax;
                    $tax->county_sales_tax = $res->countySalesTax;
                    $tax->county_use_tax = $res->countyUseTax;
                    $tax->district_sales_tax = $res->districtSalesTax;
                    $tax->district_use_tax = $res->districtUseTax;
                    $tax->city_tax_code = $res->cityTaxCode;
                    $tax->county_tax_code = $res->countyTaxCode;
                    $tax->service_taxable = $res->txbService;
                    $tax->freight_taxable = $res->txbFreight;
                    $tax->added_date = date('Y-m-d');
                    $tax->save(false);
                } //results
                $zip_codes = self::get_tax_by_zipcode($zip_code); // call same function to fetch newly imported tax rates...
            } //if results are greater then 0

        }
        return $zip_codes;
    }

    /*
     * This function will return tax rates for given zip code in form of associative array
     * It will first check for the tax rate in system, but if not found it in DB it will call
     * zip-tax API to import tax rates and again return associative array of results
     * @Param: $zip_code 
     * @Return: array of taxes in a zip code
     */
    public static function get_saletax_by_zipcode($zip_code)
    {
        Yii::import('application.modules.tax_rates.models.*');
        $zip_codes = array();


        $taxes = ZipTaxDetail::model()->findAll('zip_code=:zip_code', array(':zip_code' => $zip_code));
        if ($taxes) {
            foreach ($taxes as $addr) {
                return array('county' => $addr->county, 'state' => $addr->state,
                    'city' => $addr->city, 'sales_tax' => $addr->sales_tax, 'use_tax' => $addr->use_tax);
                break;
            }
        } else {
            Yii::import('ext.zip-tax.*');
            // Set the API key
            $apiKey = '9X696HB';
            // Instantiate the client
            $zipTax = new ZipTax($apiKey);
            $rate = $zipTax->request($zip_code);
            if (count($rate->results) > 0) {
                foreach ($rate->results as $res) {
                    $state = States::model()->find('abbreviation=:abbreviation', array(':abbreviation' => $res->geoState));

                    $tax = new ZipTaxDetail;
                    $tax->zip_code = $zip_code;
                    $tax->city = $res->geoCity;
                    $tax->county = $res->geoCounty;
                    $tax->state = $state->id;
                    $tax->sales_tax = $res->taxSales;
                    $tax->use_tax = $res->taxUse;
                    $tax->state_sales_tax = $res->stateSalesTax;
                    $tax->state_use_tax = $res->stateUseTax;
                    $tax->city_sales_tax = $res->citySalesTax;
                    $tax->city_use_tax = $res->cityUseTax;
                    $tax->county_sales_tax = $res->countySalesTax;
                    $tax->county_use_tax = $res->countyUseTax;
                    $tax->district_sales_tax = $res->districtSalesTax;
                    $tax->district_use_tax = $res->districtUseTax;
                    $tax->city_tax_code = $res->cityTaxCode;
                    $tax->county_tax_code = $res->countyTaxCode;
                    $tax->service_taxable = $res->txbService;
                    $tax->freight_taxable = $res->txbFreight;
                    $tax->added_date = date('Y-m-d');
                    $tax->save(false);
                } //results
                $zip_codes = self::get_tax_by_zipcode($zip_code); // call same function to fetch newly imported tax rates...
            } //if results are greater then 0

        }
        return $zip_codes;
    }


    /*
     * to count execution time of a script
     */
    public static function rutime($ru, $rus, $index)
    {
        return ($ru["ru_$index.tv_sec"] * 1000 + intval($ru["ru_$index.tv_usec"] / 1000))
        - ($rus["ru_$index.tv_sec"] * 1000 + intval($rus["ru_$index.tv_usec"] / 1000));
    }

    /**
     * split PDF files to get only required pages and remove unwanted pages
     * @return name of splitted PDF.
     */
    public static function _mark_check($pdf, $fdf, $winery_directory, $flatten = '')
    {

        $fdf_file = "reports/" . $fdf;
        $pdf_directory = "reports/wineries/" . $winery_directory . "/";
        $command = " " . $winery_directory . '/' . $pdf . " fill_form $fdf_file";
        $command = base64_encode($command); //encode and then decode the command string 
        $command = base64_decode($command);

        $file_name = "gen_" . $pdf;
        $output = $winery_directory . '/' . $file_name; //set name of output file
        $command = "pdftk $command output $output $flatten";
        passthru($command); //run the command
        return $file_name;

    }

    /**
     * split PDF files to get only required pages and remove unwanted pages
     * @return name of splitted PDF.
     */
    public static function _split_pdf($pdf, $start_page, $end_page)
    {
        $pdf_file = "reports/" . $pdf;
        $command = " " . $pdf_file . " cat $start_page-$end_page";
        $command = base64_encode($command); //encode and then decode the command string 
        $command = base64_decode($command);

        $file_name = "splitted-" . $start_page . '-' . $end_page . '-' . $pdf;
        $output = "reports/" . $file_name; //set name of output file
        $command = "pdftk $command output $output";
        passthru($command); //run the command
        return $file_name;

    }

    /**
     * Merge multiple PDF files into one.
     * @return name of merged PDF.
     */
    public static function _merge_pdfs($pdfs, $winery_directory, $file_name, $flatten = '')
    {

        //$pdf_directory = dirname( Yii::app()->request->scriptFile )."/reports/wineries/".$legal_name."/";
        $pdf_directory = "reports/wineries/" . $winery_directory . "/";
        $command = "";

        if (is_array($pdfs)) {
            foreach ($pdfs as $pdf) {
                $command = $command . " " . $pdf_directory . $pdf;
            }
        } else {
            return false;
        }
        $command = base64_encode($command); //encode and then decode the command string 
        $command = base64_decode($command);

        $file_name = 'final_' . $file_name;

        $output = $pdf_directory . $file_name; //set name of output file
        $command = "pdftk $command output $output $flatten";

        passthru($command); //run the command 
        return $file_name;
        //header(sprintf('Location: http://wip.simplycompliant.com/%s', $output)); //open the merged pdf file in the browser
    }

    /*
     * Calculate age by given DOB
     */

    public static function age_from_dob($dob)
    {
        return floor((time() - strtotime($dob)) / 31556926);
    }

    /**
     * to get period name with the help of start date and end date
     * @param type $start
     * @param type $end
     * @return period name
     */

    public static function convert_date_into_name($start, $end)
    {
        $get_start_month = date('n', strtotime($start));
        $get_end_month = date('n', strtotime($end));

        $get_start_year = date('Y', strtotime($start));
        $get_end_year = date('Y', strtotime($end));
        //for a monthly report start month and end months always be the same
        if ($get_start_month == $get_end_month) {
            $date = new DateTime($start);
            $for_month = $date->format('F, Y');
            return $for_month;
            //for yearly report start month and end month will not be the same, but year will be the same
        } elseif ($get_end_month - $get_start_month != 2 && $get_start_year == $get_end_year) {
            $date = new DateTime($start);
            $for_year = $date->format('Y');
            return $for_year;
            // for quarterly report, the result of end month - start month == 2
        } elseif ($get_end_month - $get_start_month == 2) {
            if ($get_start_month == 1 && $get_end_month == 3) {
                $date = new DateTime($start);
                $for_year = $date->format('Y');
                return "Quarter 1 of " . $for_year;
            } else if ($get_start_month == 4 && $get_end_month == 6) {
                $date = new DateTime($start);
                $for_year = $date->format('Y');
                return "Quarter 2 of " . $for_year;
            } else if ($get_start_month == 7 && $get_end_month == 9) {
                $date = new DateTime($start);
                $for_year = $date->format('Y');
                return "Quarter 3 of " . $for_year;
            } else if ($get_start_month == 10 && $get_end_month == 12) {
                $date = new DateTime($start);
                $for_year = $date->format('Y');
                return "Quarter 4 of " . $for_year;
            }
        }
    }

    /**
     * gets quarter start/end date either for current date or previous according to $i
     * i.e. $i=0 => current quarter, $i=1 => 1st full quarter, $i=n => n full quarter
     * @param  $i
     * @return array();
     */
    public static function get_quarter($i = 0)
    {
        $y = date('Y');
        $m = date('m');
        if ($i > 0) {
            for ($x = 0; $x < $i; $x++) {
                if ($m <= 3) {
                    $y--;
                }
                $diff = $m % 3;
                $m = ($diff > 0) ? $m - $diff : $m - 3;
                if ($m == 0) {
                    $m = 12;
                }
            }
        }
        switch ($m) {
            case $m >= 1 && $m <= 3:
                $start = $y . '-01-01 00:00:01';
                $end = $y . '-03-31 00:00:00';
                break;
            case $m >= 4 && $m <= 6:
                $start = $y . '-04-01 00:00:01';
                $end = $y . '-06-30 00:00:00';
                break;
            case $m >= 7 && $m <= 9:
                $start = $y . '-07-01 00:00:01';
                $end = $y . '-09-30 00:00:00';
                break;
            case $m >= 10 && $m <= 12:
                $start = $y . '-10-01 00:00:01';
                $end = $y . '-12-31 00:00:00';
                break;
        }
        return array(
            'start' => $start,
            'end' => $end,
            'start_nix' => strtotime($start),
            'end_nix' => strtotime($end)
        );
    }

    /**
     * This function will return all available shipping options for that State
     * Arguments: State_ID
     * Return: Array of Shipping options
     */
    public static function get_shipping_options_old($state_id)
    {
        Yii::import('application.modules.shipping.models.*');
        $returnArray = array();
        $strategy = Helpers::get_default_strategy();
        $zones = $strategy['shipping_zones'];
        $zones = explode(',', $zones);
        $types = $strategy['shipping_types'];
        $types = explode(',', $types);

        foreach ($zones as $zn) {
            $zone_states = new ShippingZoneStates;
            $zones_qry = $zone_states->findByAttributes(array('state_id' => $state_id, 'zone_id' => $zn));
            if (!empty($zones_qry)) {
                foreach ($types as $type) {
                    $shipping_rate_model = new ShippingRate;
                    $shipping_rates = $shipping_rate_model->findAllByAttributes(array('zone_id' => $zones_qry->zone_id, 'type_id' => $type, 'strategy_id' => $strategy['id']));
                    if (!empty($shipping_rates)) {
                        foreach ($shipping_rates as $shipping_rate) {
                            $shipping_type_model = new ShippingType;
                            $shipping_type = $shipping_type_model->findByPK($shipping_rate->type_id);
                            $shipping_price_model = new ShippingRatePrice;
                            $shipping_prices = $shipping_price_model->findAllByAttributes(array('rate_id' => $shipping_rate->id));
                            if (!empty($shipping_prices)) {
                                $temp_array = array();
                                $returnArray[$shipping_rate->unit_of_measure][$shipping_rate->type_id]['shipping_carrier'] = $shipping_type->carrier;
                                $returnArray[$shipping_rate->unit_of_measure][$shipping_rate->type_id]['shipping_ship_time'] = $shipping_type->ship_time;
                                $returnArray[$shipping_rate->unit_of_measure][$shipping_rate->type_id]['shipping_name'] = $shipping_type->name;
                                foreach ($shipping_prices as $shipping_price) {
                                    //echo $zone->zone_id." + ".$shipping_rate->id."+".$shipping_price->start_range."+".$shipping_price->end_range."+".$shipping_price->price."<br>";
                                    $temp_array['shipping_start_range'] = $shipping_price->start_range;
                                    $temp_array['shipping_end_range'] = $shipping_price->end_range;
                                    $temp_array['shipping_price'] = $shipping_price->price;
                                    $temp_array['shipping_repeatable'] = $shipping_price->repeatable;
                                    $temp_array['shipping_price_id'] = $shipping_price->id;
                                    $temp_array['rate_id'] = $shipping_rate->id;
                                    if ($shipping_rate->unit_of_measure == "Unit") {
                                        $returnArray[$shipping_rate->unit_of_measure][$shipping_rate->type_id][$shipping_rate->volume_limit . '-' . $shipping_rate->volume_unit][$shipping_price->id] = $temp_array;
                                    } else {
                                        $returnArray[$shipping_rate->unit_of_measure][$shipping_rate->type_id][$shipping_price->id] = $temp_array;
                                    }
                                }

                            }
                        }
                    }
                    //echo $zone->zone_id."<br>";
                }
            }
        }
        return $returnArray;
    }

    public static function get_shipping_options($state_id)
    {
        Yii::import('application.modules.shipping.models.*');
        $returnArray = array();
        $strategy = Helpers::get_default_strategy();
        $zones = $strategy['shipping_zones'];
        $zones = explode(',', $zones);
        $types = $strategy['shipping_types'];
        $types = explode(',', $types);

        foreach ($zones as $zn) {
            $zone_states = new ShippingZoneStates;
            $zones_qry = $zone_states->findByAttributes(array('state_id' => $state_id, 'zone_id' => $zn));
            if (!empty($zones_qry)) {
                foreach ($types as $type) {
                    $shipping_rate_model = new ShippingRate;
                    $shipping_rates = $shipping_rate_model->findAllByAttributes(array('zone_id' => $zones_qry->zone_id, 'type_id' => $type, 'strategy_id' => $strategy['id']));
                    if (!empty($shipping_rates)) {
                        foreach ($shipping_rates as $shipping_rate) {
                            $shipping_type_model = new ShippingType;
                            $shipping_type = $shipping_type_model->findByPK($shipping_rate->type_id);
                            $shipping_price_model = new ShippingRatePrice;
                            $shipping_prices = $shipping_price_model->findAllByAttributes(array('rate_id' => $shipping_rate->id));
                            if (!empty($shipping_prices)) {
                                $temp_array = array();
                                $returnArray[$shipping_rate->type_id]['shipping_carrier'] = $shipping_type->carrier;
                                $returnArray[$shipping_rate->type_id]['shipping_ship_time'] = $shipping_type->ship_time;
                                $returnArray[$shipping_rate->type_id]['shipping_name'] = $shipping_type->name;
                                foreach ($shipping_prices as $shipping_price) {
                                    //echo $zone->zone_id." + ".$shipping_rate->id."+".$shipping_price->start_range."+".$shipping_price->end_range."+".$shipping_price->price."<br>";
                                    $temp_array['shipping_start_range'] = $shipping_price->start_range;
                                    $temp_array['shipping_end_range'] = $shipping_price->end_range;
                                    $temp_array['shipping_price'] = $shipping_price->price;
                                    $temp_array['shipping_repeatable'] = $shipping_price->repeatable;
                                    $temp_array['shipping_price_id'] = $shipping_price->id;
                                    $temp_array['rate_id'] = $shipping_rate->id;
                                    if ($shipping_rate->unit_of_measure == "Unit") {
                                        $returnArray[$shipping_rate->type_id][$shipping_rate->unit_of_measure][$shipping_rate->volume_limit . '-' . $shipping_rate->volume_unit][$shipping_price->id] = $temp_array;
                                    } else {
                                        $returnArray[$shipping_rate->type_id][$shipping_rate->unit_of_measure][$shipping_price->id] = $temp_array;
                                    }
                                }

                            }
                        }
                    }
                }
            }
        }

        return $returnArray;
    }

    /**
     * This function will return all options against a shipping type
     * Arguments: type_id, unit
     * Return: Array of Shipping options
     */
    public static function get_rates_for_shipping_type_old($type_id, $unit = 'Unit', $bottle_sizes = array(), $strategy_id, $order_type = 'checkout')
    {
        Yii::import('application.modules.shipping.models.*');
        $returnArray = array();
        $price = 0;
        foreach ($bottle_sizes as $size) {
            $exp = explode('-', $size);
            if ($order_type == "manual") {
                $bottles_count = SalesOrders::getVolumeBasedBottlesCount($exp[0], $exp[1]);
            } else {
                $bottles_count = Store::getVolumeBasedBottlesCount($exp[0], $exp[1]);
            }
            if ($bottles_count > 0) {
                $shipping_rate_model = new ShippingRate;
                $shipping_rates = $shipping_rate_model->findByAttributes(array('type_id' => $type_id, 'strategy_id' => $strategy_id,
                    'unit_of_measure' => $unit, 'volume_limit' => $exp[0], 'volume_unit' => $exp[1]));
                if (!empty($shipping_rates)) {

                    $shipping_type_model = new ShippingType;
                    $shipping_type = $shipping_type_model->findByPK($type_id);
                    $shipping_price_model = new ShippingRatePrice;
                    $shipping_prices = $shipping_price_model->findAllByAttributes(array('rate_id' => $shipping_rates->id));
                    if (!empty($shipping_prices)) {

                        $returnArray['shipping_type_name'] = $shipping_type->name;
                        $returnArray['shipping_type_code'] = $shipping_type->code;
                        $returnArray['shipping_type_carrier'] = $shipping_type->carrier;
                        $returnArray['shipping_type_ship_time'] = $shipping_type->ship_time;
                        foreach ($shipping_prices as $shipping_price) {
                            $repeatable = $shipping_price->repeatable;
                            if ($repeatable == "Yes") {
                                $endRange = $shipping_price->end_range;
                                if ($bottles_count <= $endRange) {
                                    $price += $shipping_price->price;
                                } else {
                                    $occurances = $bottles_count / $endRange;
                                    $occurances = ceil($occurances);
                                    $price += $shipping_price->price * $occurances;
                                }
                            } else {
                                if (($bottles_count >= $shipping_price->start_range) && ($bottles_count <= $shipping_price->end_range)) {
                                    $price += $shipping_price->price;
                                }
                            }
                        }

                    }
                }
            }
        }
        $returnArray['rate'] = $price;

        return $returnArray;
    }

    public static function get_rates_for_shipping_type($type_id, $weight, $bottle_sizes = array(), $strategy_id, $order_type = 'checkout', $total_weight, $state_id)
    {
        Yii::import('application.modules.shipping.models.*');
        Yii::import('application.modules.store.models.*');
        Yii::import('application.modules.salesorders.models.*');
        $returnArray = array();
        $price = 0;
        $bottles_count = 0;

        $shipping_type_model = new ShippingType;
        $shipping_type = $shipping_type_model->findByPK($type_id);
        $returnArray['shipping_type_name'] = $shipping_type->name;
        $returnArray['shipping_type_code'] = $shipping_type->code;
        $returnArray['shipping_type_carrier'] = $shipping_type->carrier;
        $returnArray['shipping_type_ship_time'] = $shipping_type->ship_time;
        $zone_id = self::get_zone_by_state($state_id);
        //echo $type_id." TID and SID ".$strategy_id." ZONEID ".$zone_id;
        $shipping_rate_model = new ShippingRate;
        $rate = $shipping_rate_model->findByAttributes(array('type_id' => $type_id, 'strategy_id' => $strategy_id, 'zone_id' => $zone_id));

        if (!empty($rate)) {

            $shipping_price_model = new ShippingRatePrice;
            $shipping_prices = $shipping_price_model->findAllByAttributes(array('rate_id' => $rate->id));
            $unit_rate_available = false;
            if ($rate->unit_of_measure == "Unit") {
                if (isset($bottle_sizes[$rate->volume_limit . '-' . $rate->volume_unit])) {
                    if ($order_type == "manual") {
                        $bottles_count = SalesOrders::getVolumeBasedBottlesCount($rate->volume_limit, $rate->volume_unit);
                    } else {
                        $bottles_count = Store::getVolumeBasedBottlesCount($rate->volume_limit, $rate->volume_unit);
                    }
                    if (!empty($shipping_prices) && $bottles_count > 0) {
                        $unit_rate_available = true;
                        foreach ($shipping_prices as $shipping_price) {
                            $repeatable = $shipping_price->repeatable;
                            if ($repeatable == "Yes") {
                                $endRange = $shipping_price->end_range;
                                if ($bottles_count <= $endRange) {
                                    $price += $shipping_price->price;
                                } else {
                                    $occurances = $bottles_count / $endRange;
                                    $occurances = ceil($occurances);
                                    $price += $shipping_price->price * $occurances;
                                }
                            } else {
                                if (($bottles_count >= $shipping_price->start_range) && ($bottles_count <= $shipping_price->end_range)) {
                                    $price += $shipping_price->price;
                                }
                            }
                        }

                    }
                }

            } elseif ($rate->unit_of_measure == "Weight") {
                if (!empty($shipping_prices) && ($total_weight > 0 || $weight > 0)) {
                    $rate_applied = false;
                    foreach ($shipping_prices as $shipping_price) {
                        if (!$unit_rate_available) {
                            if ((!$rate_applied && $total_weight >= $shipping_price->start_range) && ($total_weight <= $shipping_price->end_range)) {
                                $price += $shipping_price->price;
                                $rate_applied = true;
                            }
                        } else {
                            if ((!$rate_applied && $weight >= $shipping_price->start_range) && ($weight <= $shipping_price->end_range)) {
                                $price += $shipping_price->price;
                                $rate_applied = true;
                            }
                        }
                    }
                }

            }

        }
        $returnArray['rate'] = $price;

        return $returnArray;
    }

    public static function get_zone_by_state($state_id)
    {
        $strategy = Helpers::get_default_strategy();
        $zones = $strategy['shipping_zones'];
        $zones = explode(',', $zones);
        $zone_id = 0;
        foreach ($zones as $zone) {
            $zone_states = new ShippingZoneStates;
            $zones_qry = $zone_states->findByAttributes(array('state_id' => $state_id, 'zone_id' => $zone));
            if (!empty($zones_qry)) {
                $zone_id = $zones_qry->zone_id;
                break;
            }
        }
        return $zone_id;
    }

    /**
     * This function will return Discount Coupon information
     * Arguments: Coupon
     * Return: Array of Coupon Details
     */
    public static function get_discount_coupon($coupon)
    {
        Yii::import('application.modules.discountcoupons.models.*');
        $model = new DiscountCoupons;
        $discount_coupon = $model->findByAttributes(array('code' => $coupon, 'status' => 1));
        $returnArray = array();
        if (!empty($discount_coupon)) {
            if ($discount_coupon->max_usage <= $discount_coupon->usage) {
                $returnArray['err_msg'] = "Your Discount Coupon has reached maximum Usage!";
            } else {
                $returnArray['coupon_id'] = $discount_coupon->id;
                $returnArray['name'] = $discount_coupon->name;
                $returnArray['code'] = $discount_coupon->code;
                $returnArray['usage'] = $discount_coupon->usage;
                $returnArray['max_usage'] = $discount_coupon->max_usage;
                $returnArray['type'] = $discount_coupon->type;
                $returnArray['amount'] = $discount_coupon->amount;
                $returnArray['min_bottles'] = $discount_coupon->min_bottles;
                $returnArray['err_msg'] = "";
            }

        } else {
            $returnArray['err_msg'] = "Your have entered an invalid Coupon!";
        }
        return $returnArray;
    }

    /**
     * This function will add addition in the usage column for coupon
     * Arguments: Coupon
     * Return: bolean
     */
    public static function addition_discount_coupon($coupon)
    {
        Yii::import('application.modules.discountcoupons.models.*');
        $discount_coupon = DiscountCoupons::model()->findByAttributes(array('code' => $coupon, 'status' => 1));
        $returnArray = array();
        if (!empty($discount_coupon)) {
            if ($discount_coupon->max_usage <= $discount_coupon->usage) {
                return FALSE;
            } else {
                $updateArray['usage'] = $discount_coupon->usage + 1;
                $discount_coupon->attributes = $updateArray;
                if ($discount_coupon->save())
                    return true;
                else
                    return false;
            }

        } else {
            return FALSE;
        }
    }

    public static function is_promocode_valid($code = '')
    {

        Yii::import('application.modules.discounts.models.*');
        $valid = false;
        if ($code != '') {
            $model = Discounts::model()->find('code=:code AND status = 1', array(':code' => $code));
            if ($model) {
                $valid = true;
            }
        }
        return $valid;
    }

    public static function Discount_info($id = 0)
    {

        Yii::import('application.modules.discounts.models.*');
        $discount = false;
        if ($id) {
            $dicsountCodeData = Yii::app()->db->createCommand()
                ->select('*')
                ->from('discounts')
                ->where('id=:id', array(':id' => $id))
                ->queryRow();
            if (count($dicsountCodeData) > 0) {
                $discount = $dicsountCodeData;
            }
        }
        return $discount;
    }

    /**
     * This function will return Automatic Discounts information
     * Arguments: number of bottles, code
     * Return: Array of Coupon Details
     */
    public static function get_auto_discount($bottels, $clubs = array(), $code = '')
    {

        Yii::import('application.modules.discounts.models.*');
        $dicsountData = array();
        $dicsountCodeData = array();
        $automatic_discount = array();
        $model = new Discounts;
        if ($code != '') {
            $dicsountCodeData = Yii::app()->db->createCommand()
                ->select('*')
                ->from('discounts')
                ->where('code=:code AND min_bottles <= :bottels AND status = 1', array(':code' => $code, ':bottels' => $bottels))
                ->queryRow();
            if (count($dicsountCodeData) > 0) {
                if ($dicsountCodeData['max_usage'] != 0) {
                    if ($dicsountCodeData['max_usage'] <= $dicsountCodeData['current_usage']) {
                        $dicsountCodeData = array();
                    }
                }
            }
        }
        $clubDis = array();
        if (count($clubs) > 0) {
            $comma_separated = implode(",", $clubs);
            $dicsountData = Yii::app()->db->createCommand()
                ->select('do.number_of_bottles, do.discount as amount, do.wine_type_id, do.club_option_id, do.club_id, do.discount_type, dc.discount_id')
                ->from('club_options do')
                ->join('discounts_clubs dc', 'do.club_option_id=dc.club_option_id')
                ->where('dc.club_option_id IN (' . $comma_separated . ') ')//, array( ':clubs'=>$comma_separated))
                ->queryAll();
            if (count($dicsountData) > 0) {
                foreach ($dicsountData as $data) {
                    $clubDis[$data['discount_id']] = $data;
                }
            } // if data > 0
        }

        $dicsount_complete_data = $model->findAll(array(
            'condition' => "status=:status AND min_bottles <= :bottels AND code = '' AND type <> 'Club Discount'",
            'params' => array(':status' => 1, ':bottels' => $bottels),
        ));

        if (!empty($dicsount_complete_data)) {
            $automatic_discount = array();
            foreach ($dicsount_complete_data as $dd) {

                if ($dd->max_usage != 0) {
                    if ($dd->max_usage > $dd->current_usage) {
                        $temp['id'] = $dd->id;
                        $temp['name'] = $dd->name;
                        $temp['type'] = $dd->type;
                        $temp['code'] = $dd->code;
                        $temp['amount'] = $dd->amount;
                        $temp['min_bottles'] = $dd->min_bottles;
                        $temp['max_usage'] = $dd->max_usage;
                        $temp['shipping_id'] = $dd->shipping_id;
                        $temp['status'] = $dd->status;
                        $automatic_discount[$dd->id] = $temp;
                    } else {
                        //$temp = array();
                        //$automatic_discount[] = $temp;
                    }
                } else {
                    $temp['id'] = $dd->id;
                    $temp['name'] = $dd->name;
                    $temp['type'] = $dd->type;
                    $temp['code'] = $dd->code;
                    $temp['amount'] = $dd->amount;
                    $temp['min_bottles'] = $dd->min_bottles;
                    $temp['max_usage'] = $dd->max_usage;
                    $temp['shipping_id'] = $dd->shipping_id;
                    $temp['status'] = $dd->status;
                    $automatic_discount[$dd->id] = $temp;
                }

            }
        }

        $returnArr['code_discount'] = $dicsountCodeData;
        $returnArr['club_discount'] = $clubDis;
        $returnArr['min_bottle_discount'] = $automatic_discount;
        return $returnArr;
    }

    public static function addition_in_discounts($discount_id)
    {
        Yii::import('application.modules.discounts.models.*');
        $discount_coupon = Discounts::model()->findByPK($discount_id);

        if (!empty($discount_coupon)) {

            $updateArray['current_usage'] = $discount_coupon->current_usage + 1;
            //$discount_coupon->attributes=$updateArray;
            $flag = $discount_coupon->updateByPk($discount_id, $updateArray);

            if ($flag)
                return true;
            else
                return false;
        } else {
            return FALSE;
        }
    }

    /*
     * full shipping information against rate id
     */
    public static function get_shipping_for_rate($rate_id)
    {

        Yii::import('application.modules.shipping.models.*');
        $returnArray = array();
        $rate_model = new ShippingRatePrice;
        $rate_data = $rate_model->findbyPK($rate_id);
        if (!empty($rate_data)) {
            $returnArray['shipping_price_id'] = $rate_data->id;
            $returnArray['shipping_start_range'] = $rate_data->start_range;
            $returnArray['shipping_end_range'] = $rate_data->end_range;
            $returnArray['shipping_price'] = $rate_data->price;
            $returnArray['repeatable'] = $rate_data->repeatable;
            if (!empty($rate_data->rate_id)) {
                $rate = new ShippingRate;
                $rateData = $rate->findbyPK($rate_data->rate_id);
                if (!empty($rateData)) {
                    if (!empty($rateData->type_id)) {
                        $returnArray['shipping_type_id'] = $rateData->type_id;
                        $shipping_type = new ShippingType;
                        $shipping_type_date = $shipping_type->findbyPK($rateData->type_id);
                        if (!empty($shipping_type_date)) {
                            $returnArray['shipping_type_name'] = $shipping_type_date->name;
                            $returnArray['shipping_type_code'] = $shipping_type_date->code;
                            $returnArray['shipping_type_carrier'] = $shipping_type_date->carrier;
                            $returnArray['shipping_type_ship_time'] = $shipping_type_date->ship_time;
                        }
                    }

                }
            }
        }
        return $returnArray;
    }

    /*
     * return default strategy
     */
    public static function get_default_strategy()
    {
        Yii::import('application.modules.shipping.models.*');
        $strategy = Yii::app()->db->createCommand()
            ->select('*')
            ->from('shipping_strategy')
            ->where('default_strategy = "Yes"')
            ->queryRow();
        return $strategy;
    }


    /*
     * return Email Template Array
     * Argument as Email template Key/Action
     */
    public static function getEmailTemplate($key)
    {

        Yii::import('application.modules.emailtemplates.models.*');
        $template = EmailTemplates::model()->findByAttributes(array('key' => $key));
        return $template;

    }

    public static function clean_url($str, $replace = array(), $delimiter = '-')
    {
        if (!empty($replace)) {
            $str = str_replace((array)$replace, ' ', $str);
        }

        $clean = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
        $clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
        $clean = strtolower(trim($clean, '-'));
        $clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);

        return $clean;
    }


    public static function is_home()
    {

        $isFrontpage = false;
        if ((Yii::app()->controller->getId() . '/' . Yii::app()->controller->getAction()->getId()) == 'site/index') {
            $isFrontpage = true;
        }

        return $isFrontpage;

    }

    public static function UserLifeTimeValue($id)
    {

        $orders = Yii::app()->db->createCommand()
            ->select('SUM(order_total) as ltv')
            ->from('sales_orders')
            ->where(" `customer_id` = " . $id . " AND `order_status` = 'Completed' ")
            ->queryRow();
        if (!empty($orders['ltv'])) {
            return number_format($orders['ltv'], 2);
        } else {
            return '0.00';
        }
    }

    /*
     * Helper Function to return default Payment Gateway and Latest Tokens for payment
     * Return Type: Array
     * If return['error'] equal to empty then no error and have correct data
    */

    public static function getPaymentTokens()
    {

        $returnArray = array();
        $settings = new Settings;
        $meta = $settings->find();
        $returnArray['payment_gateway'] = $meta->default_payment;

        if ($meta->default_payment == 'Authorize.net Payment Gateway') {

            $user_id = Yii::app()->getUsersIDFromMasterDB();
            $connection = Yii::app()->openMasterDbConnection();
            $sql = "SELECT * FROM user_authorize_info WHERE user_id = $user_id";
            $command = $connection->createCommand($sql);
            $result = $command->queryRow();
            Yii::app()->closeMasterDbConnection($connection);

            if (!empty($result)) {
                $returnArray['error'] = '';
                $returnArray['data'] = $result;
            } else {
                $returnArray['error'] = 'No Data Found';
                $returnArray['data'] = $result;
            }
        } elseif ($meta->default_payment == 'Stripe Payment Gateway') {

            $data = Yii::app()->getLatestStripeInfo();
            if ($data != 'Stripe Info Not Found') {
                $returnArray['error'] = '';
                $returnArray['data'] = $data;
            } else {
                $returnArray['error'] = 'No data Found';
                $returnArray['data'] = '';
            }
        } else {

            $data = Yii::app()->getLatestStripeInfo();
            if ($data != 'Stripe Info Not Found') {
                $returnArray['error'] = '';
                $returnArray['data'] = $data;
            } else {
                $returnArray['error'] = 'No data Found';
                $returnArray['data'] = '';
            }

        }
        return $returnArray;
    }

    public static function safe_b64encode($string)
    {

        $data = base64_encode($string);
        $data = str_replace(array('+', '/', '='), array('-', '_', ''), $data);
        return $data;
    }

    public static function safe_b64decode($string)
    {
        $data = str_replace(array('-', '_'), array('+', '/'), $string);
        $mod4 = strlen($data) % 4;
        if ($mod4) {
            $data .= substr('====', $mod4);
        }
        return base64_decode($data);
    }

    public static function encode($value)
    {

        if (!$value) {
            return false;
        }
        $skey = "MyencondedKey";
        $text = $value;
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $crypttext = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $skey, $text, MCRYPT_MODE_ECB, $iv);
        return trim(Helpers::safe_b64encode($crypttext));
    }

    public static function decode($value)
    {

        if (!$value) {
            return false;
        }
        $skey = "MyencondedKey";
        $crypttext = Helpers::safe_b64decode($value);
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $decrypttext = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $skey, $crypttext, MCRYPT_MODE_ECB, $iv);
        return trim($decrypttext);
    }

}

?>
