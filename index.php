
<?php include('server.php'); ?>


<!DOCTYPE html>
<html>
<head>
<title>Mobiux Assignment</title>
</head>
<body >
	<form action="" method="POST">
	  <label>Choose From Date</label>
	  <input type="date" name="from_date">
	  <br/>
	  <label>Choose To Date</label>
	  <input type="date" name="to_date">
	  <br/>
	  <input type="submit" name="submit" value="submit">
	</form>
    <section >
        <p>Total sales of the store : <?php echo $totalSales; ?> </p>
    </section>
    <section>
        <p>Month wise sales totals</p>
        <table border="1">
    		<th>Year - Month</th>
    		<th>Sales</th>    	
    	<?php foreach (json_decode($monthWiseSalesTotal) as $key =>$value) {?>
    		<tr>
    			<td><?php echo $key ?></td>
    			<td><?php echo $value ?></td>
    		</tr>    		
    	<?php }
    	?>
    	</table>

    </section>

    <section>
    	<p>Most popular item (most quantity sold) in each month.
    		<?php foreach ($mostPopularItems as $key => $mostPopularItemRecord) { ?>
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
    	    <?php }?>

    	</p>
    </section>
    <section>
    	<p>Items generating most revenue in each month</p>
    	<table border="1">
    		<th>Year - Month</th>
    		<th>Product Name</th>    	
    	<?php foreach ($mostRevenueItemsInMonth as $key =>$value) {?>
    		<tr>
    			<td><?php echo $key ?></td>
    			<td><?php echo $value ?></td>
    		</tr>    		
    	<?php }
    	?>
    	</table>
    </section>
    <section>
    	<p>For the most popular item, find the min, max and average number of orders each month.</p>
    	<table border="1">
    		<th>Year - Month</th>
    		<th>MinOrderPerMonth</th>  
    		<th>MaxOrderPerMonth</th>  
    		<th>AvgOrderPerMonth</th>  
    	<?php foreach (json_decode($minMaxandAvgOrderPerMonth) as $key => $value) {?>
    		<tr>
    			<td><?php echo $key ?></td>
    			<?php foreach ($value as $key => $value1) {?>
    				<td><?php echo $value1 ?></td>
    			<?php } ?>
    		</tr>
    	<?php } ?>
    </section>
</body>
</html>

