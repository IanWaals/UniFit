<!DOCTYPE html>
<html>
<head>
  <title>UniFit</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap">
  <link rel="stylesheet" href="style-specklas.css">
</head>
<body>

<button id="menu-button" onclick="toggleMenu()">☰</button>

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

<div id="content">
  <div class="leaderboard">
    <div class="card">
      <h3>Klas NL-01</h3>
      <div class="scroll-list">
        <table id="leaderboard-table">
          <tr>
            <th onclick="sortTable(0)">Naam</th>
            <th onclick="sortTable(1)">Level</th>
            <th onclick="sortTable(2)">Aanwezigheid gem.</th>
          </tr>
          <tr><td>Mark Rutte</td><td>2</td><td>92%</td></tr>
          <tr><td>Willem Alexander</td><td>1</td><td>74%</td></tr>
          <tr><td>Jan Smit</td><td>5</td><td>87%</td></tr>
          <tr><td>Gerard Joling</td><td>3</td><td>90%</td></tr>
          <tr><td>Sierd de Vos</td><td>4</td><td>85%</td></tr>
          <tr><td>Vincent van Gogh</td><td>6</td><td>80%</td></tr>
          <tr><td>Geert Wilders</td><td>7</td><td>75%</td></tr>
          <tr><td>Hans Klok</td><td>8</td><td>91%</td></tr>
          <tr><td>Ben Strik</td><td>9</td><td>100%</td></tr>
          <tr><td>André Hazes</td><td>10</td><td>86%</td></tr>
          <tr><td>Max Verstappen</td><td>5</td><td>94%</td></tr>
          <tr><td>Linda de Mol</td><td>2</td><td>90%</td></tr>
          <tr><td>Johan Cruijff</td><td>8</td><td>87%</td></tr>
        </table>
      </div>
    </div>
  </div>
</div>

<script src="script.js"></script>
</body>
</html>
