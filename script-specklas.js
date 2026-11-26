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

function sortTable(n) {
  var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
  table = document.getElementById("leaderboard-table");
  switching = true;
  dir = "asc";
  while (switching) {
    switching = false;
    rows = table.rows;
    for (i = 1; i < (rows.length - 1); i++) {
      shouldSwitch = false;
      x = rows[i].getElementsByTagName("TD")[n];
      y = rows[i + 1].getElementsByTagName("TD")[n];
      var xValue = x.innerHTML.trim();
      var yValue = y.innerHTML.trim();
      var xNumber = Number(xValue);
      var yNumber = Number(yValue);
      var comparison;
      if (xValue !== "" && yValue !== "" && isFinite(xNumber) && isFinite(yNumber)) {
        comparison = xNumber - yNumber;
      } else {
        comparison = xValue.localeCompare(yValue, undefined, { numeric: true, sensitivity: "base" });
      }
      if (dir == "asc") {
        if (comparison > 0) {
          shouldSwitch = true;
          break;
        }
      } else if (dir == "desc") {
        if (comparison < 0) {
          shouldSwitch = true;
          break;
        }
      }
    }
    if (shouldSwitch) {
      rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
      switching = true;
      switchcount ++;
    } else {
      if (switchcount == 0 && dir == "asc") {
        dir = "desc";
        switching = true;
      }
    }
  }
}
