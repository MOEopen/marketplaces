<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <title></title>
    <link rel="stylesheet" type="text/css" href="Marketplaces.css" media="all" />
    <script type="text/javascript" src="http://code.jquery.com/jquery-1.9.1.min.js"></script>
    <script type="text/javascript" src="http://cdn.jquerytools.org/1.2.7/full/jquery.tools.min.js"></script>
  </head>
  <body>
    <div style="margin-bottom:10px;">
      <a href="index.php?Job=Export" style="border:1px solid black; padding:5px 20px; margin-right:20px; background-color:#E0E0E0;">Daten exportieren</a>
      <a href="index.php?Job=Edit" style="border:1px solid black; padding:5px 20px; margin-right:20px; background-color:#E0E0E0;">Daten bearbeiten</a>
      <a href="index.php?Job=DelCache" style="border:1px solid black; padding:5px 20px; margin-right:20px; background-color:#E0E0E0;">Cache l&ouml;schen</a>
      <a href="index.php?Job=TransDb" style="border:1px solid black; padding:5px 20px; margin-right:20px; background-color:#E0E0E0;">Transfer Db</a>
    </div>
    <?php 
      require_once('inc/controller.php');
    ?>
  </body>
</html>