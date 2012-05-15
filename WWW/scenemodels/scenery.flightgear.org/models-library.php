<?php include("include/menu.php"); ?>
<div id="main">

  <div class="postHeaderCompact">
    <div class="inner">
      <a title="Permalink to Home" href="http://www.flightgear.org/"><h1 class="postTitle">3D Models Library</h1></a>
      <div class="bottom">
        <span></span>
      </div>
    </div>
  </div>

  <div class="postContent">

    <h1>Add a new 3D model</h1>
    <p>
      In order to add a new 3D model you must use this form : <a href="#">Add a new 3D model</a>
    </p>


    <h1>Update/delete 3D model</h1>
    <p>
      Currently it's not possible to update/delete a 3D model. 3D models are automatically deleted if not used (no available position).
    </p>


    <h1>Authors</h1>
    <p>
      Here is the list of all authors, thanks to them :<br/>
      $LIST-OF-AUTHORS
    </p>


    <h1>3D models library</h1>
    <fieldset>
      <legend>Filter</legend>
      Sort by: <select><option>author|last update|name</option></select>
      Family: <select><option>All|Static|Shared...</option></select>
      Number of objects/page: <select><option>10|100|500|1000</option></select>
      View: <select><option>list|grid</option></select>
      <button style="float:right;">Filter</button>
    </fieldset>
    <p>
      $LIST-OF-MODELS
    </p>
    <br/>


  </div>

</div>
<?php include("include/footer.php"); ?>
