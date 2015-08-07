<?php 
/**
 * DEPRECATED : use view/error_page.php instead
 * 
 * Error page
 * To use this page, please instantiate the following strings:
 * - $pageTitle   : page's title
 * - $process_text : contains the actual process
 * - $errorText   : contains the error message
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
<?php echo $errorText;?>
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