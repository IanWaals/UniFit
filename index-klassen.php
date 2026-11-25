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

  <h1>Klassen</h1>

  <div class="subjects">

    <div class="responsive">
      <div class="gallery">
        <a href="#">
          <img src="https://www.deeeuw.nl/upload/thumbs/600x450_841-2010_2011_A3A.jpg">
        </a>
        <div class="desc">NL-01</div>
      </div>
    </div>

    <div class="responsive">
      <div class="gallery">
        <a href="#">
          <img src="https://www.heugem.net/foto/overige/klassefoto/middelbaar/0506klassefotobanti3.jpg">
        </a>
        <div class="desc">EN-01</div>
      </div>
    </div>

    <div class="responsive">
      <div class="gallery">
        <a href="#">
          <img src="https://www.heugem.net/foto/overige/klassefoto/middelbaar/Klassefotoh2b.jpg" alt="Wiskunde">
        </a>
        <div class="desc">NL-02</div>
      </div>
    </div>

    <div class="responsive">
      <div class="gallery">
        <a href="#">
          <img src="https://www.deeeuw.nl/upload/125-1992-1993_Havo_4b.jpg" alt="Nederlands">
        </a>
        <div class="desc">EN-02</div>
      </div>
    </div>

    <div class="responsive">
      <div class="gallery">
        <a href="#">
          <img src="https://thumbs.dreamstime.com/b/de-zegel-van-het-voorbeeld-28420393.jpg" alt="Geschiedenis">
        </a>
        <div class="desc">NL-03</div>
      </div>
    </div>

    <div class="responsive">
      <div class="gallery">
        <a href="#">
          <img src="https://thumbs.dreamstime.com/b/de-zegel-van-het-voorbeeld-28420393.jpg" alt="Biologie">
        </a>
        <div class="desc">EN-03</div>
      </div>
    </div>

    <div class="responsive">
      <div class="gallery">
        <a href="#">
          <img src="https://thumbs.dreamstime.com/b/de-zegel-van-het-voorbeeld-28420393.jpg" alt="Scheikunde">
        </a>
        <div class="desc">NL-04</div>
      </div>
    </div>

    <div class="responsive">
      <div class="gallery">
        <a href="#">
          <img src="https://thumbs.dreamstime.com/b/de-zegel-van-het-voorbeeld-28420393.jpg" alt="Economie">
        </a>
        <div class="desc">EN-04</div>
      </div>
    </div>

    <div class="responsive">
      <div class="gallery">
        <a href="#">
          <img src="https://thumbs.dreamstime.com/b/de-zegel-van-het-voorbeeld-28420393.jpg" alt="Lichamelijke opvoeding">
        </a>
        <div class="desc">NL-05</div>
      </div>
    </div>

    <div class="clearfix"></div>

  </div>
</div>

<script src="script.js"></script>
</body>
</html>
