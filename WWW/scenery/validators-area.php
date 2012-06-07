<?php include("include/menu.php"); ?>
<div id="main">

  <div class="postHeaderCompact">
    <div class="inner">
      <a title="Permalink to Home" href="http://www.flightgear.org/"><h1 class="postTitle">Validators area</h1></a>
      <div class="bottom">
        <span></span>
      </div>
    </div>
  </div>

  <div class="postContent">


    <h1>Become a validator</h1>
    <p>
Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum. 
    </p>
    <br/>


    <h1>Validators login</h1>
    <form action="validator/index.php" method="post">
      <table>
        <tr>
          <td>Username: </td>
          <td><input type="text" name="login"/></td>
        </tr>
        <tr>
          <td>Password: </td>
          <td><input type="passwd" name="passwd"/></td>
        </tr>
        <tr>
          <td></td>
          <td align="right"><input type="submit" value="Login"/></td>
        </tr>
        <tr>
          <td colspan="2" align="right"><a href="#">Forgot password</a></td>
        </tr>
      </table>
    </form>

  </div>

</div>
<?php include("include/footer.php"); ?>
