const svg = document.getElementById("lines");
const mapaContainer = document.getElementById("mapa");

const colors = {
  odio: "#c0392b",
  afecto: "#f1c40f",
  amistad: "#f1c40f",
  amor: "#27ae60",
  relations: "#3498db"
};

// Store all lines for hover effects
let allLines = [];

// Set SVG dimensions
const rect = mapaContainer.getBoundingClientRect();
svg.setAttribute("width", rect.width);
svg.setAttribute("height", rect.height);

// Helper function to draw lines
function drawLines() {
  svg.innerHTML = "";
  allLines = []; // Reset lines array
  
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
    line.style.transition = "opacity 0.2s ease, stroke-width 0.2s ease";
    
    // Store line data for hover effects
    allLines.push({
      element: line,
      from: r.from,
      to: r.to
    });
    
    svg.appendChild(line);
  });
  
  // Add hover listeners to all nodes
  document.querySelectorAll('.node').forEach(node => {
    node.addEventListener('mouseenter', () => highlightNode(node.id));
    node.addEventListener('mouseleave', resetHighlight);
  });
}

// Highlight lines connected to a node
function highlightNode(nodeId) {
  allLines.forEach(lineData => {
    if (lineData.from === nodeId || lineData.to === nodeId) {
      // This line is connected to the hovered node
      lineData.element.setAttribute("opacity", "1");
      lineData.element.setAttribute("stroke-width", "3");
    } else {
      // This line is not connected
      lineData.element.setAttribute("opacity", "0.15");
      lineData.element.setAttribute("stroke-width", "2");
    }
  });
}

// Reset all lines to default state
function resetHighlight() {
  allLines.forEach(lineData => {
    lineData.element.setAttribute("opacity", "0.7");
    lineData.element.setAttribute("stroke-width", "2");
  });
}

// Initial draw
drawLines();

// Redraw lines on window resize
window.addEventListener("resize", () => {
  const newRect = mapaContainer.getBoundingClientRect();
  svg.setAttribute("width", newRect.width);
  svg.setAttribute("height", newRect.height);
  
  drawLines();
});