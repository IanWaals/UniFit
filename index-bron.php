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

  <a href="#"><i class="fa fa-home"></i> Dashboard</a>
  <a href="#"><i class="fa fa-book"></i> Vakken</a>
  <a href="#"><i class="fa fa-calendar"></i> Rooster</a>
  <a href="#"><i class="fa fa-trophy"></i> Leaderboard</a>
  <a href="#" class="active-page"><i class="fa fa-folder-o"></i> Bronnen</a>
  <a href="#"><i class="fa fa-line-chart"></i> Cijfers</a>

  <div class="sidebar-bottom">
    <a href="#"><i class="fa fa-cog"></i> Instellingen</a>
    <a href="#"><i class="fa fa-question-circle-o"></i> Help</a>
    <a href="#"><i class="fa fa-sign-out"></i> Log uit</a>
  </div>
</div>

<button id="menu-button" onclick="toggleMenu()">☰</button>

<div id="main">
 <div class="pagina">
  <h1>Tweede Wereldoorlog</h1>

  <div class="bronnen-container">

    <div class="bron">
      <h2 id="bron-titel">Bron 1</h2>
      <p id="bron-tekst">De Tweede Wereldoorlog begon in 1939, toen Duitsland Polen binnenviel.</p>
    </div>
    <div class="bronnen-lijst">
      <button class="bron-knop" onclick="kiesBron(1)">Bron 1</button>
      <button class="bron-knop" onclick="kiesBron(2)">Bron 2</button>
      <button class="bron-knop" onclick="kiesBron(3)">Bron 3</button>
      <button class="bron-knop" onclick="kiesBron(4)">Bron 4</button>
    </div>
  </div>
 </div>
</div>

<script src="script.js"></script>
</body>
</html>
