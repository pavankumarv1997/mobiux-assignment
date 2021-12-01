
<?php

$server = include('server.php'); 

if(isset($graphData)){
    print_r($graphData);
}
?>


<!DOCTYPE html>
<html>
<head>
<title>Mobiux Assignment</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.js"></script>
</head>
<body >
    <p>Pie chart for Jan 2019 </p>
    <?php
    $responseLength = count($graphData);

    arsort($graphData);
    print_r($graphData);
    $sortedArray = [];

    // foreach($graphData as $key=>$value) {
    //     $sortedArray[$key] = $value;
    // }
    // print_r($sortedArray);

    $graphKeys = array_slice(array_keys($graphData), 1);
    $graphValues = array_slice(array_values($graphData), 1);

    // $age=array("Peter"=>"35","Ben"=>"37","Joe"=>"43");

     // echo count(array_keys($graphData)); 
     ?>
  
<!--     <p id="graphKeys"><?php echo json_encode(array_keys($graphData)) ?></p>
     <p id="graphValues"><?php echo json_encode(array_values($graphData)) ?></p> -->
    <canvas id="myChart" style="width:100%;max-width:89%"></canvas>
	<form action="" method="POST">
	  <label>Choose From Date</label>
	  <input type="date" name="from_date" required>
	  <br/>
	  <label>Choose To Date</label>
	  <input type="date" name="to_date" required>
	  <br/>
	  <input type="submit" name="submit" value="Get Data">
	</form>
    <br/>
     <section>
        JSON RESPONSE
        <?php if(isset($server)){print_r($server);}  ?>
    </section>
    <br>
    <section >
        <p>Total sales of the store : <?php 
            if(isset($totalSales)){
                echo $totalSales;
            }
       ?> </p>
    </section>
    <section>
        <p>Month wise sales totals</p>
        <table border="1">
    		<th>Year - Month</th>
    		<th>Sales</th>    	
    	<?php 
        if(isset($monthWiseSalesTotal)){
            foreach (json_decode($monthWiseSalesTotal) as $key =>$value) {?>
                <tr>
                    <td><?php echo $key ?></td>
                    <td><?php echo $value ?></td>
                </tr>           
            <?php }
        } ?>
    	</table>

    </section>

    <section>
    	<p>Most popular item (most quantity sold) in each month.
    		<?php if(isset($mostPopularItems)) {
                foreach ($mostPopularItems as $key => $mostPopularItemRecord) { ?>
        			<h1><?php echo $key ?></h1>
        			<table border="1">
    		    		<th>Year - Month</th>
    		    		<th>Product Name</th>  
    	    			<?php foreach ($mostPopularItemRecord as $key => $value) {?>
    	    				<tr>
    			    			<td><?php echo $key ?></td>
    			    			<td><?php echo $value ?></td>
    			    		</tr>     				
    	    			<?php } ?>
    	    		</table>
        	    <?php }
            }?>

    	</p>
    </section>
    <section>
    	<p>Items generating most revenue in each month</p>
    	<table border="1">
    		<th>Year - Month</th>
    		<th>Product Name</th>    	
    	<?php if(isset($mostRevenueItemsInMonth)){
            foreach ($mostRevenueItemsInMonth as $key =>$value) {?>
    		<tr>
    			<td><?php echo $key ?></td>
    			<td><?php echo $value ?></td>
    		</tr>    		
    	<?php }
        }?>
    	</table>
    </section>
    <section>
    	<p>For the most popular item, find the min, max and average number of orders each month.</p>
    	<table border="1">
    		<th>Year - Month</th>
    		<th>MinOrderPerMonth</th>  
    		<th>MaxOrderPerMonth</th>  
    		<th>AvgOrderPerMonth</th>  
    	<?php 
        if(isset($minMaxandAvgOrderPerMonth)){
            foreach (json_decode($minMaxandAvgOrderPerMonth) as $key => $value) {?>
            <tr>
                <td><?php echo $key ?></td>
                <?php foreach ($value as $key => $value1) {?>
                    <td><?php echo $value1 ?></td>
                <?php } ?>
            </tr>
        <?php } 

        } ?>
        
    </section>



<script>

var xValues = ["Italy", "France", "Spain", "USA", "Argentina"];
var yValues = [55, 49, 44, 24, 15];
// var xValues = document.getElementById('graphKeys').innerHTML;
// var yValues = document.getElementById('graphKeys').innerHTML;
var xValues = <?php echo json_encode($graphKeys) ?>;
var yValues = <?php echo json_encode($graphValues) ?>;

var barColors = [
 "#b91d47",
  "#00aba9",
  "#2b5797",
  "#e8c3b9",
  "#1e7145",
  "#b91d47",
  "#00aba9",
  "#2b5797",
  "#e8c3b9",
  "#1e7145",
  "#b91d47",
  "#00aba9",
  "#2b5797",
  "#e8c3b9",
  "#1e7145",
  "#b91d47",
  "#00aba9",
  "#2b5797",
  "#e8c3b9",
  "#1e7145",
 "#b91d47",
  "#00aba9",
  "#2b5797",
  "#e8c3b9",
  "#1e7145",
  "#b91d47",

];

new Chart("myChart", {
  type: "pie",
  data: {
    labels: xValues,
    datasets: [{
      backgroundColor: barColors,
      data: yValues
    }]
  },
  options: {
    title: {
      display: true,
      text: "World Wide Wine Production 2018"
    }
  }
});
</script>
<script>
    var responseKeys = document.getElementById('graphKeys').innerHTML;
    var responseValues = document.getElementById('graphValues').innerHTML;
    // var keys = Object.keys(response);
    console.log(responseKeys);
    console.log(responseValues);

</script>
</body>
</html>

