<?php 
/**
 * Error page
 * To use this page, please instantiate the following strings:
 * - $page_title   : page's title
 * - $process_text : contains the actual process
 * - $error_text   : contains the error message
 * - $advise_text  : contains advise about what to about to correct the error
 *
**/

require "header.php"; ?>


<p class="center">
<?php 
    if(isset($process_text)) {
        echo $process_text;
    }
?>
</p>

<p class="center warning">
<?php echo $error_text;?>
</p>

<p class="center">
<?php 
    if(isset($advise_text)) {
        echo $advise_text;
    }
?>
</p>

<p class="center">The FlightGear team.</p>


<?php require "footer.php"; ?>