<?php include("include/menu.php"); ?>
<div id="main">

  <div class="postHeaderCompact">
    <div class="inner">
      <a title="Permalink to Home" href="http://www.flightgear.org/"><h1 class="postTitle">Objects position</h1></a>
      <div class="bottom">
        <span></span>
      </div>
    </div>
  </div>

  <div class="postContent">

    <h1>Add a new object position</h1>
    <p>
      In order to add a new object position you must use this form : <a href="#">Add a new object position</a>
    </p>


    <h1>Update/delete object position</h1>
    <p>
      In order to update/delete an object position you need to browse our objects positions library available in this page.
    </p>


    <h1>Objects positions library</h1>
    <fieldset>
      <legend>Filter</legend>
      <table width="1036px">
        <tr>
          <td>Latitude:</td><td> <input type="text"/></td>
          <td>Longitude:</td><td> <input type="text"/></td>
          <td>Ground elevation (m):</td><td> <input type="text"/></td>
        </tr>
        <tr>
          <td>Elevation offset:</td><td> <input type="text"/></td>
          <td>Heading:</td><td> <input type="text"/></td>
          <td>Description:</td><td> <input type="text"/></td>
        </tr>
        <tr>
          <td>Model:</td><td> <select><option>$LIST-OF-MODELS</option></select></td>
          <td>Group:</td><td> <select><option>$LIST-OF-GROUP</option></select></td>
          <td>Country:</td><td> <select><option>$LIST-OF-COUNTRY</option></select></td>
        </tr>
        <tr>
          <td colspan="6">      <button style="float:right;">Filter</button></td>
        </tr>
      </table>
    </fieldset>
    <p>
      $LIST-OF-OBJECTS
    </p>
    <br/>


  </div>

</div>
<?php include("include/footer.php"); ?>
