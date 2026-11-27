function toggleMenu() {
  var sidebar = document.getElementById("sidebar");
  var menuButton = document.getElementById("menu-button");
  var main = document.getElementById("main");

  if (sidebar.style.width == "250px") {
    sidebar.style.width = "0";
    menuButton.style.marginLeft = "16px";
    main.style.marginLeft = "40px";
  } else {
    sidebar.style.width = "250px";
    menuButton.style.marginLeft = "266px";
    main.style.marginLeft = "290px";
  }
}

function kiesBron(nummer) {
  var titel = document.getElementById("bron-titel");
  var tekst = document.getElementById("bron-tekst");

  if (nummer == 1) {
    titel.innerHTML = "Bron 1";
    tekst.innerHTML = "De Tweede Wereldoorlog begon in 1939, toen Duitsland Polen binnenviel.";
  } else if (nummer == 2) {
    titel.innerHTML = "Bron 2";
    tekst.innerHTML = "Nederland werd op 10 mei 1940 door Duitsland binnengevallen en was vijf dagen later bezet.";
  } else if (nummer == 3) {
    titel.innerHTML = "Bron 3";
    tekst.innerHTML = "Tijdens de bezetting waren er voedseltekorten, censuur en vervolging. Veel mensen kwamen in verzet.";
  } else if (nummer == 4) {
    titel.innerHTML = "Bron 4";
    tekst.innerHTML = "De Tweede Wereldoorlog eindigde in Europa in mei 1945. Nederland viert de bevrijding op 5 mei.";
  }
}
