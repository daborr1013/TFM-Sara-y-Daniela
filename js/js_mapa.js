const svg = document.getElementById("lines");

const colors = {
  odio: "#c0392b",
  afecto: "#f1c40f",
  amistad: "#3498db",
  amor: "#27ae60"
};

svg.setAttribute("width", "100%");
svg.setAttribute("height", "100%");

relations.forEach(r => {
  const from = document.getElementById(r.from);
  const to = document.getElementById(r.to);

  const x1 = from.offsetLeft + from.offsetWidth / 2;
  const y1 = from.offsetTop + from.offsetHeight / 2;
  const x2 = to.offsetLeft + to.offsetWidth / 2;
  const y2 = to.offsetTop + to.offsetHeight / 2;

  const line = document.createElementNS("http://www.w3.org/2000/svg", "line");
  line.setAttribute("x1", x1);
  line.setAttribute("y1", y1);
  line.setAttribute("x2", x2);
  line.setAttribute("y2", y2);
  line.setAttribute("stroke", colors[r.type]);
  line.setAttribute("stroke-width", "3");

  svg.appendChild(line);
});
``