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
    
    const dx = x2 - x1;
    const dy = y2 - y1;
    
    // Add a slight curve by offsetting the control point perpendicularly
    const cx = (x1 + x2) / 2 - dy * 0.15;
    const cy = (y1 + y2) / 2 + dx * 0.15;
    
    const path = document.createElementNS("http://www.w3.org/2000/svg", "path");
    const d = `M ${x1} ${y1} Q ${cx} ${cy} ${x2} ${y2}`;
    path.setAttribute("d", d);
    path.setAttribute("stroke", colors[r.type] || "#999");
    path.setAttribute("fill", "none");
    path.setAttribute("stroke-width", "3");
    path.setAttribute("opacity", "0.6");
    path.style.transition = "opacity 0.3s ease, stroke-width 0.3s ease";
    
    // Custom dash arrays for different relations
    if (r.type === 'odio') {
      path.setAttribute("stroke-dasharray", "6,6");
    } else if (r.type === 'relations') {
      path.setAttribute("stroke-dasharray", "2,4");
    }
    
    // Store line data for hover effects
    allLines.push({
      element: path,
      from: r.from,
      to: r.to
    });
    
    svg.appendChild(path);
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
      lineData.element.setAttribute("stroke-width", "5");
    } else {
      // This line is not connected
      lineData.element.setAttribute("opacity", "0.1");
      lineData.element.setAttribute("stroke-width", "2");
    }
  });
}

// Reset all lines to default state
function resetHighlight() {
  allLines.forEach(lineData => {
    lineData.element.setAttribute("opacity", "0.6");
    lineData.element.setAttribute("stroke-width", "3");
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