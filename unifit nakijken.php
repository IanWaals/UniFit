<!DOCTYPE html>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body {
  font-family: "Poppins", sans-serif;
  margin: 0;
  min-height: 100vh;
  background: linear-gradient(135deg, #344B32, #1B251C);
}
#sidebar {
  position: fixed;
  top: 0;
  left: 0;
  width: 0;
  height: 100%;
  background-color: #101010;
  overflow: hidden;
  white-space: nowrap;
  transition: 0.5s;
}
#sidebar .logo {
  font-size: 32px;
  font-weight: bold;
  color: #668F57;
  padding: 20px 32px 30px;
}

#sidebar a {
  display: block;
  color: #E6E6E6;
  text-decoration: none;
  padding: 10px 16px;
  margin: 2px 16px;
  border-radius: 8px;
  transition: 0.5s;
}
#sidebar a i {
  width: 20px;
  margin-right: 10px;
}

#sidebar a:hover {
  color: #668F57;
  transition: 0.5s;
}

#sidebar a.active-page {
  color: #668F57;
  background-color: #202020;
}

#sidebar .sidebar-bottom {
  position: absolute;
  bottom: 20px;
  width: 100%;
}
#menu-button {
  margin: 16px;
  font-size: 20px;
  color: #E6E6E6;
  background-color: #101010;
  padding: 10px 15px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: 0.5s;
}

#menu-button:hover {
  background-color: #202020;
  transition: 0.5s;
}

#main {
  transition: margin-left .5s;
  padding: 16px;
}

/* On smaller screens, where height is less than 450px, change the style of the sidenav (less padding and a smaller font size) */
@media screen and (max-height: 450px){
  .sidebar {padding-top: 15px;}
  .sidebar a {font-size: 18px;}
}
.container{
    display: flex;
    justify-content: space-betweem;
    gap: 15px;
  }
.extra{
    flex: 1;
    padding: 15px;
  }
.kleuredarkmode{
    flex: 1;
    border-style: solid;
  border-width: 1px;
  border-color: #D6D6D6;
    background-color: #202020;
    padding:8px;
    font-size: 25px;
    color: #D6D6D6;
  }
.nextkleuredarkmode{
  float: right;
  border-style: solid;
  border-width: 1px;
  border-color: #D6D6D6;
    background-color: #202020;
    padding:8px;
    font-size: 25px;
    text-align: left;
    color: #D6D6D6;
  }
  .onderkleuredarkmode{
    float: bottom;
    border-style: solid;
  border-width: 1px;
  border-color: #D6D6D6;
    background-color: #202020;
    padding:8px;
    font-size: 25px;
    color: #D6D6D6;
  }
.nextextra{
  float: right;
    padding: 35px;
  }
.progressie{
    flex: 1;
    border-style: solid;
  border-width: 1px;
  border-color: #D6D6D6;
    background-color: #202020;
    padding:10px;
    font-size: 20px;
    color: #D6D6D6;
  }
body {
    background: linear-gradient(to bottom, #172b23 0%, #174b23 100%);
}
  
</style>
<head>
  <title>UniFit</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap">
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

<button id="menu-button" onclick="toggleMenu()">☰</button>

<div class="extra">
    <div class="container">
    <div class="extra">
      <div class="kleuredarkmode">
        <h2>vraag:</h2>
        <p1>de vraag</p1>
<!--Database uit-->
        </div>
        <div class="onderkleuredarkmode">
          <p1>andwoordll</p1>
<!--database uit-->
      </div>
      <div class="onderkleuredarkmode">
          <p1>feedback</p1>
<!--database in-->
      </div>
      </div>
      <div class="extra">
    <div class="kleuredarkmode">
        <h2>naam</h2>
        <p1>text<br><br><br><br><br><br><br><br>
        </p1>
<!--database uit-->
      </div>
      </div>
    </div>
  </div>

  
  <div class="nextextra">
    <div class="nextkleuredarkmode">
      <p1>next</p1>
<!--functie-->
    </div>
  </div>
<script>
  function toggleMenu() {
  var sidebar = document.getElementById("sidebar");
  var menuButton = document.getElementById("menu-button");
  var content = document.getElementById("content");

  if (sidebar.style.width == "250px") {
    sidebar.style.width = "0";
    menuButton.style.marginLeft = "16px";
    content.style.marginLeft = "0";
  } else {
    sidebar.style.width = "250px";
    menuButton.style.marginLeft = "266px";
    content.style.marginLeft = "250px";
  }
}
</script>
</body>
</html>