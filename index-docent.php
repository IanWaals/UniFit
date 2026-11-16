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
  <a href="#"><i class="fa fa-users"></i> Klassen</a>
  <a href="#"><i class="fa fa-calendar"></i> Rooster</a>
  <a href="#"><i class="fa fa-trophy"></i> Leaderboard</a>
  <a href="#"><i class="fa fa-bell"></i> Meldingen</a>

  <div class="sidebar-bottom">
    <a href="#"><i class="fa fa-cog"></i> Instellingen</a>
    <a href="#"><i class="fa fa-sign-out"></i> Log uit</a>
  </div>
</div>

<button id="menu-button" onclick="toggleMenu()">☰</button>

<div id="content">

  <h1>Wiskunde</h1>
  <div class="subjects-list">
    <a href="#">Hoofdstuk 1: Getallen</a>
    <a href="#">Hoofdstuk 2: Algebra</a>
    <a href="#">Hoofdstuk 3: Geometrie</a>
    <a href="#">Hoofdstuk 4: Statistiek</a>
    <a href="#">Hoofdstuk 5: Kansrekening</a>
    <a href="#">Hoofdstuk 6: Formules</a>
    <a href="#">Hoofdstuk 7: Grafieken</a>
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
            <td class="lesson-name"><b>Wiskunde</b><br><small>NL01 - Lokaal 4</small></td>
          </tr>
          <tr class="current-lesson">
            <td class="lesson-time"><b>09:10</b><br><small>10:00</small></td>
            <td class="lesson-name"><b>Wiskunde</b><br><small>EN07 - Lokaal 4</small></td>
          </tr>
          <tr>
            <td class="lesson-time"><b>10:20</b><br><small>11:10</small></td>
            <td class="lesson-name"><b>Wiskunde</b><br><small>NL04 - Lokaal 4</small></td>
          </tr>
          <tr>
            <td class="lesson-time"><b>11:10</b><br><small>12:00</small></td>
            <td class="lesson-name"><b>Wiskunde</b><br><small>EN02 - Lokaal 4</small></td>
          </tr>
          <tr>
            <td class="lesson-time"><b>12:00</b><br><small>12:50</small></td>
            <td class="lesson-name"><b>Wiskunde</b><br><small>EN03 - Lokaal 4</small></td>
          </tr>
        </table>
      </div>
    </div>

    <div class="card">
      <h3>Meldingen</h3>
      <div class="scroll-list">
        <table>
          <tr>
            <th>Naam</th>
            <th>Type</th>
            <th>Tijd geleden</th>
          </tr>
          <tr><td>Hans Klok</td><td>Vraag</td><td>16 sec</td></tr>
          <tr><td>Réne le Blanc</td><td>Vraag</td><td>2 min</td></tr>
          <tr><td>Rico verhoeven</td><td>Nakijken</td><td>5 min</td></tr>
          <tr><td>Vladimir Putin</td><td>Vraag</td><td>10 min</td></tr>
          <tr><td>Ronnie Flex</td><td>Nakijken</td><td>12 min</td></tr>
          <tr><td>Rapper Sjors</td><td>Nakijken</td><td>16 min</td></tr>
        </table>
      </div>
    </div>

  </div>
</div>

<script src="script.js"></script>
</body>
</html>
