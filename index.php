<?php

include "src/dbConnect.php";
$students = getStudents();
$sampleStudent = $students[0];

?>

<!DOCTYPE html>
<html>
<head>
  <title>UniFit</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap">
  <link rel="stylesheet" href="style.css">
</head>
<body>

<div id="sidebar">
  <div class="logo">UniFit</div>

  <a href="#" class="active-page"><i class="fa fa-home"></i> Dashboard</a>
  <a href="#"><i class="fa fa-book"></i> Vakken</a>
  <a href="#"><i class="fa fa-calendar"></i> Rooster</a>
  <a href="#"><i class="fa fa-trophy"></i> Leaderboard</a>
  <a href="#"><i class="fa fa-folder-o"></i> Bronnen</a>
  <a href="#"><i class="fa fa-line-chart"></i> Cijfers</a>

  <div class="sidebar-bottom">
    <a href="#"><i class="fa fa-cog"></i> Instellingen</a>
    <a href="#"><i class="fa fa-question-circle-o"></i> Help</a>
    <a href="#"><i class="fa fa-sign-out"></i> Log uit</a>
  </div>
</div>

<div>
  <button id="menu-button" onclick="toggleMenu()">☰</button>
  <p>Welcome to UniFit, <?php echo $sampleStudent['full_name']</p>
</div>

<div id="content">

  <h1>Vakken</h1>
  <div class="subjects-list">
    <a href="#">Aardrijkskunde</a>
    <a href="#">Engels</a>
    <a href="#">Wiskunde</a>
    <a href="#">Nederlands</a>
    <a href="#">Geschiedenis</a>
    <a href="#">Biologie</a>
    <a href="#">Economie</a>
    <a href="#">Lichamelijke opvoeding</a>
    <a href="#">Scheikunde</a>
  </div>

  <div class="schedule-and-leaderboard">

    <div class="card">
      <h3>
        <button class="arrow arrow-left"><i class="fa fa-chevron-left"></i></button>
        Rooster
        <button class="arrow arrow-right"><i class="fa fa-chevron-right"></i></button>
      </h3>
      <div class="scroll-list">
        <table>
          <tr>
            <th colspan="2">Woensdag 11 nov</th>
          </tr>
          <tr>
            <td class="lesson-time"><b>08:20</b><br><small>09:10</small></td>
            <td class="lesson-name"><b>Wiskunde</b><br><small>J. Jansen - Lokaal 21</small></td>
          </tr>
          <tr class="current-lesson">
            <td class="lesson-time"><b>09:10</b><br><small>10:00</small></td>
            <td class="lesson-name"><b>Biologie</b><br><small>E. de Vries - Lokaal 13</small></td>
          </tr>
          <tr>
            <td class="lesson-time"><b>10:20</b><br><small>11:10</small></td>
            <td class="lesson-name"><b>Engels</b><br><small>M. Bakker - Lokaal 32</small></td>
          </tr>
          <tr>
            <td class="lesson-time"><b>11:10</b><br><small>12:00</small></td>
            <td class="lesson-name"><b>Aardrijkskunde</b><br><small>P. Peters - Lokaal 7B</small></td>
          </tr>
          <tr>
            <td class="lesson-time"><b>12:00</b><br><small>12:50</small></td>
            <td class="lesson-name"><b>Nederlands</b><br><small>K. de Boer - Lokaal 4</small></td>
          </tr>
        </table>
      </div>
    </div>

    <div class="card">
      <h3>Leaderboard</h3>
      <div class="scroll-list">
        <table>
          <tr>
            <th></th>
            <th>Naam</th>
            <th>Score</th>
          </tr>
          <?php 
          $counter = 0;
          foreach($students as $student) {
            $counter += 1;
            ?><tr><td><?php echo $counter; ?></td><td><?php echo $student['full_name']; ?></td><td><?php echo $student['score']; ?></td></tr><?php
          }
          ?>
          // <tr><td>1</td><td>Mark Rutte</td><td>985</td></tr>
          // <tr><td>2</td><td>Willem Alexander</td><td>920</td></tr>
          // <tr><td>3</td><td>Jan Smit</td><td>875</td></tr>
          // <tr><td>4</td><td>Gerard Joling</td><td>830</td></tr>
          // <tr><td>5</td><td>Sierd de Vos</td><td>790</td></tr>
          // <tr><td>6</td><td>Vincent van Gogh</td><td>745</td></tr>
          // <tr><td>7</td><td>Geert Wilders</td><td>700</td></tr>
          // <tr><td>8</td><td>Hans Klok</td><td>655</td></tr>
          // <tr><td>9</td><td>Ben Strik</td><td>610</td></tr>
        </table>
      </div>
    </div>

  </div>
</div>

<script src="script.js"></script>
</body>
</html>
