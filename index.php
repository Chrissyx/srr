<?php
 include("twrr.php");
?>
<html>
 <head>
  <title>Tiberium Wars Replay Reader</title>
  <meta name="author" content="Chrissyx">
 </head>
 <body>
 <?php
if (is_uploaded_file($_FILES['replay']['tmp_name']))
{
 copy($_FILES['replay']['tmp_name'], $_FILES['replay']['name']) or die("<b>ERROR</b>: Upload fehlgeschlagen!");
 //move_uploaded_file($_FILES['replay']['tmp_name'], $_FILES['replay']['name']) or die("<b>ERROR</b>: Upload fehlgeschlagen!");
 print_r(openReplay($_FILES['replay']['name']));
 unlink($_FILES['replay']['name']);
 echo "<br />\n  <a href=\"" . $_SERVER['PHP_SELF'] . "\">Zurück</a>\n";
}
else
{
?>
  <form action="<?=$_SERVER['PHP_SELF']?>" method="post" enctype="multipart/form-data">
  <input type="file" name="replay"><br />
  <input type="submit" value="Hochladen"> <input type="reset">
  </form>
<?php
}
?>
 </body>
</html>