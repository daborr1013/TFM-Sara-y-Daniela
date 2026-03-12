# TFM-Sara-y-Daniela

html
<button onclick="abrirModal()">Haz click</button>

<div id="miModal" class="modal">
  <div class="modal-contenido">
    <span onclick="cerrarModal()">&times;</span>
    <p>Texto aquí</p>
  </div>
</div>


css
.modal {
  display: none; 
  position: fixed;
  z-index: 1;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0,0,0,0.5);
}

.modal-contenido {
  background: white;
  margin: 15% auto;
  padding: 20px;
  width: 300px;
  border-radius: 10px;
  text-align: center;
}

.cerrar {
  float: right;
  font-size: 25px;
  cursor: pointer;
}


js

function abrirModal() {
  document.getElementById("miModal").style.display = "block";
}

function cerrarModal() {
  document.getElementById("miModal").style.display = "none";
}

const modal = document.getElementById("miModal");

window.onclick = function(event) {
  if (event.target == modal) {
    modal.style.display = "none";
  }
}


