
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
