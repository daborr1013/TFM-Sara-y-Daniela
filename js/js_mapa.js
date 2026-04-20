const svg = document.getElementById("lines");
const mapaContainer = document.getElementById("mapa");

const colors = {
  odio: "#c0392b",
  afecto: "#f1c40f",
  amistad: "#f1c40f",
  amor: "#27ae60",
  relations: "#3498db"
};

// Set SVG dimensions
const rect = mapaContainer.getBoundingClientRect();
svg.setAttribute("width", rect.width);
svg.setAttribute("height", rect.height);

// Draw lines for each relationship
relations.forEach(r => {
  const from = document.getElementById(r.from);
  const to = document.getElementById(r.to);
  
  if (!from || !to) return; // Skip if elements not found
  
  // Get center positions of nodes
  const fromRect = from.getBoundingClientRect();
  const toRect = to.getBoundingClientRect();
  const mapaRect = mapaContainer.getBoundingClientRect();
  
  const x1 = fromRect.left - mapaRect.left + fromRect.width / 2;
  const y1 = fromRect.top - mapaRect.top + fromRect.height / 2;
  const x2 = toRect.left - mapaRect.left + toRect.width / 2;
  const y2 = toRect.top - mapaRect.top + toRect.height / 2;
  
  // Create SVG line
  const line = document.createElementNS("http://www.w3.org/2000/svg", "line");
  line.setAttribute("x1", x1);
  line.setAttribute("y1", y1);
  line.setAttribute("x2", x2);
  line.setAttribute("y2", y2);
  line.setAttribute("stroke", colors[r.type] || "#999");
  line.setAttribute("stroke-width", "2");
  line.setAttribute("opacity", "0.7");
  
  svg.appendChild(line);
});

// Redraw lines on window resize
window.addEventListener("resize", () => {
  svg.innerHTML = "";
  const newRect = mapaContainer.getBoundingClientRect();
  svg.setAttribute("width", newRect.width);
  svg.setAttribute("height", newRect.height);
  
  relations.forEach(r => {
    const from = document.getElementById(r.from);
    const to = document.getElementById(r.to);
    
    if (!from || !to) return;
    
    const fromRect = from.getBoundingClientRect();
    const toRect = to.getBoundingClientRect();
    const mapaRect = mapaContainer.getBoundingClientRect();
    
    const x1 = fromRect.left - mapaRect.left + fromRect.width / 2;
    const y1 = fromRect.top - mapaRect.top + fromRect.height / 2;
    const x2 = toRect.left - mapaRect.left + toRect.width / 2;
    const y2 = toRect.top - mapaRect.top + toRect.height / 2;
    
    const line = document.createElementNS("http://www.w3.org/2000/svg", "line");
    line.setAttribute("x1", x1);
    line.setAttribute("y1", y1);
    line.setAttribute("x2", x2);
    line.setAttribute("y2", y2);
    line.setAttribute("stroke", colors[r.type] || "#999");
    line.setAttribute("stroke-width", "2");
    line.setAttribute("opacity", "0.7");
    
    svg.appendChild(line);
  });
});