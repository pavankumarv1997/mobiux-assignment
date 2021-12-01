<?php


  include('config.php');

  if (($open = fopen("sales-data.csv", "r")) !== FALSE) 
  {
    while (($data = fgetcsv($open, 0, ",")) !== FALSE) 
    {        
      $dataSet[] = $data; 
    }
  
    fclose($open);
  }
  // echo "<pre>";

  Global $from_date_p;

  try{
  	$response = [];
	if(isset($_POST['submit'])) {
		$from_date_p = $_POST['from_date'];
		$to_date_p =  $_POST['to_date'];
		$filteredData = array();
		foreach($dataSet as $salesItems){
		    if(strtotime($salesItems[0]) >= strtotime($from_date_p) AND strtotime($salesItems[0]) <= strtotime($to_date_p)){ 
		        $labels[] = $salesItems[0];
		        $values[] = $salesItems[4];
		        $filteredData[] = $salesItems;
		    }

		}
		if (count($filteredData) > 0 && isset($filteredData)){	
			// print_r($dataSet);
			array_unshift($filteredData, $dataSet[0]);		
				
			$totalSales = getTotalSales($filteredData);
			$monthWiseSalesTotal = getMonthWiseSalesTotal($filteredData);
			$mostPopularItems = getMostPopularAndRevenueItems($filteredData,QUANTITY);
			$mostRevenueItemsInMonth = $mostPopularItems['mostRevenueItemPerMonth'];
			$popularItems = array_unique(array_values($mostPopularItems['mostPopularItemPerMonth']));
			$minMaxandAvgOrderPerMonth = getMinMaxandAvgOrderPerMonth($filteredData,$popularItems);	  
			$response['filtered'] = 1;
			 $response['totalSales'] = $totalSales;
			$response['monthWiseSalesTotal'] = json_decode($monthWiseSalesTotal);
			$response['mostPopularItems'] = $mostPopularItems['mostPopularItemPerMonth'];
			$response['mostRevenueItemsInMonth'] = $mostRevenueItemsInMonth;
			$response['minMaxandAvgOrdersPerMonth'] = json_decode($minMaxandAvgOrderPerMonth);
			 	
		}else{
			echo "Sorry No records found";
		}	   
  
	}else{
		$graphData = getMonthWiseSalesTotalforGraph($dataSet);
		$totalSales = getTotalSales($dataSet);
		$monthWiseSalesTotal = getMonthWiseSalesTotal($dataSet);
		$mostPopularItems = getMostPopularAndRevenueItems($dataSet,'quantity');
		$mostRevenueItemsInMonth = $mostPopularItems['mostRevenueItemPerMonth'];
		$popularItems = array_unique(array_values($mostPopularItems['mostPopularItemPerMonth']));
		$minMaxandAvgOrderPerMonth = getMinMaxandAvgOrderPerMonth($dataSet,$popularItems);
		$response['filtered'] = 0;
		 $response['totalSales'] = $totalSales;
		$response['monthWiseSalesTotal'] = json_decode($monthWiseSalesTotal);
		$response['mostPopularItems'] = $mostPopularItems['mostPopularItemPerMonth'];
		$response['mostRevenueItemsInMonth'] = $mostRevenueItemsInMonth;
		$response['minMaxandAvgOrdersPerMonth'] = json_decode($minMaxandAvgOrderPerMonth);
	}
       

	  return json_encode($response);
  }catch(Exception $e){
  	echo 'Error: ' .$e->getMessage();
  }

  function getSearchIndex($type,$headerRow){  	 
  	return  array_search($type, $headerRow);
  }
  function getTotalSales($dataSet){
  	// read first row, convert row values to lowercase and find quantity column position
  	$total = [];
  	// $headerRow = array_map('strtolower', $dataSet[0]);  	
  	array_shift($dataSet);
	$total = array_column($dataSet, 4);
  	return array_sum($total);
  }

  function getUniqueDateMonthYear($dataSet1,$searchIndex,$type){
  	foreach ($dataSet1 as $salesData) {
  		$salesDate[] =  ($salesData[$searchIndex]);	  		
  	}
	array_shift($salesDate);
	if ($type == "month"){
		$uniqueMonths = array_unique(array_map(function($date) {
	    	return DateTime::createFromFormat('Y-m-d', $date)->format("m");
		}, $salesDate));
	}elseif ($type == "year") {
		$uniqueMonths = array_unique(array_map(function($date) {
	    	return DateTime::createFromFormat('Y-m-d', $date)->format("Y");
		}, $salesDate));
	}elseif ($type == "day"){
		$uniqueMonths = array_unique(array_map(function($date) {
	    	return DateTime::createFromFormat('Y-m-d', $date)->format("d");
		}, $salesDate));
	}
	
	return $uniqueMonths;
  }

  function getMonthWiseSalesTotal($dataSet){
  	// read csv , get index, get unique month , get sales total
  	$headerRow = array_map('strtolower', $dataSet[0]);
  	$searchIndex = getSearchIndex(DATE,$headerRow);
  	$uniqueDates = getUniqueDateMonthYear($dataSet,$searchIndex,"day");
	$uniqueMonths = getUniqueDateMonthYear($dataSet,$searchIndex,"month");	
	$uniqueYears = getUniqueDateMonthYear($dataSet,$searchIndex,"year");
	
	$result = [];
	$labels = array();
	$values = array();	
	foreach ($uniqueMonths as $key => $month) {
		
		$from_date = current($uniqueYears).'-'.$month.'-'.current($uniqueDates);
    	$to_date = end($uniqueYears).'-'.$month.'-'.cal_days_in_month(CAL_GREGORIAN,$month,end($uniqueYears));
		    
	    foreach($dataSet as $salesItems){
	        if(strtotime($salesItems[0]) >= strtotime($from_date) AND strtotime($salesItems[0]) <= strtotime($to_date)){ 
	            $labels[] = $salesItems[0];
	            $values[] = $salesItems[4];
	        }
	    }
	    $result[current($uniqueYears).'-'.$month] = array_sum($values);
	    $values = [];		   
	}
	return json_encode($result);
	
	
  }

   function getMonthWiseSalesTotalforGraph($dataSet){
  	// read csv , get index, get unique month , get sales total
  	$headerRow = array_map('strtolower', $dataSet[0]);
  	$searchIndex = getSearchIndex(DATE,$headerRow);

  	$uniqueDates = getUniqueDateMonthYear($dataSet,$searchIndex,"day");
	$uniqueMonths = getUniqueDateMonthYear($dataSet,$searchIndex,"month");	
	$uniqueYears = getUniqueDateMonthYear($dataSet,$searchIndex,"year");
	
	$result = [];
	$labels = array();
	$values = array();	
	$productsList = [];
	$monthWiseQtyData = [];
	$monthWiseSalesData = [];
	foreach ($uniqueMonths as $key => $month) {
		
		// $from_date = current($uniqueYears).'-'.$month.'-'.current($uniqueDates);
  //   	$to_date = end($uniqueYears).'-'.$month.'-'.cal_days_in_month(CAL_GREGORIAN,$month,end($uniqueYears));
		$from_date = '2019-01-01';
		$to_date = '2019-01-31';
		
	    foreach($dataSet as $salesItems){
	        if(strtotime($salesItems[0]) >= strtotime($from_date) AND strtotime($salesItems[0]) <= strtotime($to_date)){ 
	            $labels[] = $salesItems[0];
	            $values[] = $salesItems[4];
	        }
	        if(isset($monthWiseQtyData[$month][$salesItems[1]])){
	            	$monthWiseQtyData[$month][$salesItems[1]] += $salesItems[3];
            }else{
            	$monthWiseQtyData[$month][$salesItems[1]] = $salesItems[3];
            }
            if(isset($monthWiseSalesData[$month][$salesItems[1]])){
            	$monthWiseSalesData[$month][$salesItems[1]] += $salesItems[4];
            }else{
            	$monthWiseSalesData[$month][$salesItems[1]] = $salesItems[4];
            }

	    }
	    // $result[current($uniqueYears).'-'.$month] = array_sum($values);
	    // $values = [];		 
	    return ($monthWiseSalesData['01']); 
	    // break; 
	}
	return json_encode($result);
	
	
  }

  function getMostPopularAndRevenueItems($dataSet,$searchIndexColumn){
  	// month wise data , sum of quantity sold per day 
  	$headerRow = array_map('strtolower', $dataSet[0]);
  	$searchIndex = getSearchIndex(DATE,$headerRow);
  	$quantitySearchIndex = getSearchIndex($searchIndexColumn,$headerRow);
  	$productSearchIndex =  getSearchIndex($searchIndexColumn,$headerRow);
  	$priceSearchIndex = getSearchIndex(TOTAL_PRICE,$headerRow);
  	$uniqueDates = getUniqueDateMonthYear($dataSet,$searchIndex,"day");
	$uniqueMonths = getUniqueDateMonthYear($dataSet,$searchIndex,"month");	
	$uniqueYears = getUniqueDateMonthYear($dataSet,$searchIndex,"year");

	$products = array_column($dataSet, 1);
	$productUList = array_unique($products);
	$productsList = array_slice($productUList,1);
	$result = [];
	$labels = array();
	$values = array();	
	$productList = [];
	$monthWiseQtyData = [];
	$monthWiseSalesData = [];

	foreach ($uniqueMonths as $key => $month) {
	    $from_date = current($uniqueYears).'-'.$month.'-'.current($uniqueDates);
	    $to_date = end($uniqueYears).'-'.$month.'-'.cal_days_in_month(CAL_GREGORIAN,$month,end($uniqueYears));
   		foreach($dataSet as $salesItems){
   			if(strtotime($salesItems[0]) >= strtotime($from_date) AND strtotime($salesItems[0]) <= strtotime($to_date)){ 
	            $labels[] = $salesItems[0];
	            $values[] = $salesItems;
	            // for both most popular and revenue item
	            if(isset($monthWiseQtyData[$month][$salesItems[1]])){
	            	$monthWiseQtyData[$month][$salesItems[1]] += (int)$salesItems[$quantitySearchIndex];
	            }else{
	            	$monthWiseQtyData[$month][$salesItems[1]] = (int)$salesItems[$quantitySearchIndex];
	            }
	            if(isset($monthWiseSalesData[$month][$salesItems[1]])){
	            	$monthWiseSalesData[$month][$salesItems[1]] += (int)$salesItems[$priceSearchIndex];
	            }else{
	            	$monthWiseSalesData[$month][$salesItems[1]] = (int)$salesItems[$priceSearchIndex];
	            }
	        }	   			
	    }		 
	   	$salesMax = array_sum($monthWiseSalesData[$month]);
	   	$salesMax = max(array_values($monthWiseSalesData[$month]));
	    $mostRevenueItemPerMonth = array_search($salesMax, $monthWiseSalesData[$month]);
	    $max = max(array_values($monthWiseQtyData[$month]));
	    $key = array_search($max, $monthWiseQtyData[$month]);	  
	    $result['mostPopularItemPerMonth'][current($uniqueYears).'-'.$month]  = $key;
	    $result['mostRevenueItemPerMonth'][current($uniqueYears).'-'.$month]  = $mostRevenueItemPerMonth;	    
	}
	return $result;
  }

  function getMinMaxandAvgOrderPerMonth($dataSet,$mostPopularItem){
  	$headerRow = array_map('strtolower', $dataSet[0]);
  	$searchIndex = getSearchIndex(DATE,$headerRow);
  	$quantitySearchIndex = getSearchIndex(QUANTITY,$headerRow);
  	$uniqueDates = getUniqueDateMonthYear($dataSet,$searchIndex,"day");
	$uniqueMonths = getUniqueDateMonthYear($dataSet,$searchIndex,"month");	
	$uniqueYears = getUniqueDateMonthYear($dataSet,$searchIndex,"year");
	$result = [];
	$labels = array();
	$values = array();	
	$quantity = [];
	$productList = [];
	$uniqueDays = [];
	$daysSales = [];
	foreach ($uniqueMonths as $key => $month) {
	    $from_date = current($uniqueYears).'-'.$month.'-'.current($uniqueDates);
	    $to_date = end($uniqueYears).'-'.$month.'-'.cal_days_in_month(CAL_GREGORIAN,$month,end($uniqueYears));
	    foreach($dataSet as $salesItems){
	        if(strtotime($salesItems[0]) >= strtotime($from_date) AND strtotime($salesItems[0]) <= strtotime($to_date) AND $salesItems[1] == $mostPopularItem[0]){ 
	            $labels[] = $salesItems[0];
	            $values[] = $salesItems;
	            $quantity[] = $salesItems[$quantitySearchIndex];
	            $productList[] = $salesItems[1];
	            $uniqueDays[] = $salesItems[0];
	            if ($month == date('m', strtotime($salesItems[0]))){
		   			if(in_array($salesItems[0], array_keys($daysSales)) ){		   				
		    			$daysSales[$salesItems[0]] += (int)$salesItems[$quantitySearchIndex];  
			    	}else{
			    		$daysSales[$salesItems[0]] =  (int)$salesItems[$quantitySearchIndex];
			    	}
		   		}
	        }
	    }  
	    // print_r($daysSales);
	    $maximumOrdersPerMonth = (int)max($daysSales);
	    $minimumOrdersPerMonth = (int)min($daysSales);
	    $averageOrdersPerMonth = (array_sum($daysSales)/cal_days_in_month(CAL_GREGORIAN,$month,end($uniqueYears)));	  
	    $daysSales = [];	  
	    $result[current($uniqueYears).'-'.$month]['minimumOrdersPerMonth'] = $minimumOrdersPerMonth;
	    $result[current($uniqueYears).'-'.$month]['maximumOrdersPerMonth'] = $maximumOrdersPerMonth;
	    $result[current($uniqueYears).'-'.$month]['averageOrdersPerMonth'] = number_format((float)$averageOrdersPerMonth, 2, '.', '');
	}
	return json_encode($result);
  }		


?>

