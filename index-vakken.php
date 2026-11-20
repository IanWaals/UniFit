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
  <a href="#" class="active-page"><i class="fa fa-book"></i> Vakken</a>
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

<button id="menu-button" onclick="toggleMenu()">☰</button>

<div id="content">

  <h1>Vakken</h1>

  <div class="subjects">

    <div class="responsive">
      <div class="gallery">
        <a href="#">
          <img src="https://www.thuisstudiereviews.nl/wp-content/uploads/2022/06/aardrijkskunde-min.jpg" alt="Aardrijkskunde">
        </a>
        <div class="desc">Aardrijkskunde <span class="level">lvl 5</span></div>
      </div>
    </div>

    <div class="responsive">
      <div class="gallery">
        <a href="#">
          <img src="https://didactiefonline.nl/uploads/content-images/zaakvakken%20en%20creatieve%20vakken/shutterstock_-Engels-onderwijs-1000-px.jpg" alt="Engels">
        </a>
        <div class="desc">Engels <span class="level">lvl 3</span></div>
      </div>
    </div>

    <div class="responsive">
      <div class="gallery">
        <a href="#">
          <img src="https://static3.depositphotos.com/1003799/167/i/450/depositphotos_1677021-stock-photo-math.jpg" alt="Wiskunde">
        </a>
        <div class="desc">Wiskunde <span class="level">lvl 4</span></div>
      </div>
    </div>

    <div class="responsive">
      <div class="gallery">
        <a href="#">
          <img src="https://www.travelvalley.nl/wp-content/uploads/headers/nederland-land-toekomst.jpg" alt="Nederlands">
        </a>
        <div class="desc">Nederlands <span class="level">lvl 2</span></div>
      </div>
    </div>

    <div class="responsive">
      <div class="gallery">
        <a href="#">
          <img src="https://www.geschiedenisvandaag.nu/wp-content/uploads/2024/03/Banner-Geschiedenis-Vandaag-nieuw.jpg" alt="Geschiedenis">
        </a>
        <div class="desc">Geschiedenis <span class="level">lvl 6</span></div>
      </div>
    </div>

    <div class="responsive">
      <div class="gallery">
        <a href="#">
          <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR1Hdakbx-3fqbz5Kerp6ckPul66NQ6dE1secg1vzcGv0WAD338T1GIZh3w&s=10" alt="Biologie">
        </a>
        <div class="desc">Biologie <span class="level">lvl 1</span></div>
      </div>
    </div>

    <div class="responsive">
      <div class="gallery">
        <a href="#">
          <img src="https://images.ctfassets.net/huogrpkfou0w/3757FQg38EWjWfUVfsEFC3/02d21ca0b8b884889e7282bf497a0da1/scheikunde_mengen_550.jpg" alt="Scheikunde">
        </a>
        <div class="desc">Scheikunde <span class="level">lvl 2</span></div>
      </div>
    </div>

    <div class="responsive">
      <div class="gallery">
        <a href="#">
          <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTk_KqFwXrgMluDMEv1IH8VG0lD-VsRHsBdaI_sm4y6-s_CHOXQI8A2yOY&s=10" alt="Economie">
        </a>
        <div class="desc">Economie <span class="level">lvl 3</span></div>
      </div>
    </div>

    <div class="responsive">
      <div class="gallery">
        <a href="#">
          <img src="https://www.leraar24.nl/app/uploads/Marieke-Duijsters_ok.jpg" alt="Lichamelijke opvoeding">
        </a>
        <div class="desc">Lichamelijke opvoeding <span class="level">lvl 4</span></div>
      </div>
    </div>

    <div class="clearfix"></div>

  </div>
</div>

<script src="script.js"></script>
</body>
</html>
