<?php
$pageTitle = "Automated Objects Massive Import Submission Form";
require 'view/header.php';
?>
<script src="/inc/js/check_form.js" type="text/javascript"></script>
<script type="text/javascript">
/*<![CDATA[*/
function validateForm()
{
    var form = document.getElementById("positions");

    if (!checkStringNotDefault(form["stg"], "") || !checkSTG(form["stg"]) ||
        (form['email'].value!=="" && !checkEmail(form['email'])) ||
        !checkStringNotDefault(form["comment"], "") || !checkComment(form["comment"]))
        return false;
}
/*]]>*/
</script>

<h1>Automated Objects Massive Import Submission Form</h1>

<p>
  There are currently <?php echo number_format($nbObjects, '0', '', ' ');?> objects in the database.
  Help us to make it more! Simply copy/paste the NEW content of your STG files below.
</p>
<p>
  Please read <a href="http://<?php echo $_SERVER['SERVER_NAME']; ?>/contribute.php">this page</a>
  in order to understand what recommandations this script is looking for.
</p>
<p>Please note that:</p>

<ul class="warning">
    <li>Only add NEW objects !</li>
    <li>
      Do not insert models not existing in the scenery objects database,
      nor OBJECT_SIGN, nor OBJECT_STATIC lines.
    </li>
    <li>
      Do <strong>NOT</strong> add forests or items linked to the landcover.
      Those have to be generated by the landmass layers! Only trees
      or equivalent natural boundaries within an airport will be accepted.
    </li>
    <li>
      Pitch or roll information are ignored.
      <strong>Elevation is ignored. Only offset elevation is used.</strong>
    </li>
    <li>100 lines maximum per submission!</li>
    <li>
      You can copy/paste objects positions coming from different STG files.
    </li>
</ul>

<ul>
<li>Line without offset, pitch and roll:<br/>
    OBJECT_SHARED Models/Airport/localizer.xml 121.337467 31.179872 2.47 267.03</li>
<li>Line with offset:<br/>
    OBJECT_SHARED Models/Airport/localizer.xml 121.337467 31.179872 2.47 267.03 -20.0</li>
<li>Line with pitch and roll (both ignored), but without offset:<br/>
    OBJECT_SHARED Models/Airport/localizer.xml 121.337467 31.179872 2.47 267.03 0.0 0.0</li>
</ul>


<form id="positions" method="post" action="app.php?c=AddObjects&amp;a=confirmMass" onsubmit="return validateForm();">
<table>
    <tr>
        <td style="vertical-align: top;"><label for="stg">Content to add<em>*</em><span>This is the content of the STG file you want to add.</span></label></td>
        <td><textarea name="stg" id="stg" rows="30" cols="100" onchange="checkSTG(this);"></textarea></td>
    </tr>
    <tr>
        <td><label for="email">Email address<span>Please leave YOUR VALID email address over here. This will help you be informed of your submission process.</span></label></td>
        <td>
            <input type="text" name="email" id="email" maxlength="50" size="50" value="" onkeyup="checkEmail(this);" />
        </td>
    </tr>
    <tr>
        <td><label for="comment">Comment<em>*</em><span>Please add a short (max 100 letters) statement why you are inserting this data. This will help the maintainers understand what you are doing. eg: 'I have placed a couple of aircraft shelters and static F16's at EHVK, please commit.' Only alphanumerical, colon, semi colon, question and exclamation mark, arobace, minus, underscore, antislash and point are granted.</span></label></td>
        <td>
            <input type="text" name="comment" id="comment" maxlength="100" size="100" value="" onkeyup="checkComment(this);" />
        </td>
    </tr>
    <tr>
        <td colspan="2" class="submit">
            <input type="hidden" name="step" value="1"/>
            <input type="submit" value="Submit mass import" />
        </td>
    </tr>
</table>
</form>

<?php require 'view/footer.php';
?>