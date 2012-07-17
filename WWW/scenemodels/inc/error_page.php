<?php include "header.php"; ?>
<p class="center warning">
<?php echo $error_text;?>
</p>
<p class="center">
<?php 
    if(isset($advise_text))
        echo $advise_text;
?>
</p>
<p class="center">The FlightGear team.</p>
<?php include "footer.php"; ?>
