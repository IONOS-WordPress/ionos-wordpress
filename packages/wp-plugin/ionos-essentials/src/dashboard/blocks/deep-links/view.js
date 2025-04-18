function updateColumns() {
  const grid = document.getElementById('deeplinks-elements');
  const itemWidth = 180;
  const containerWidth = grid.offsetWidth;
  const columns = Math.floor(containerWidth / itemWidth);
  const totalItems = grid.children.length;
  const rows = 2;
  const maxColumns = Math.ceil(totalItems / rows);
  const finalColumns = Math.min(columns, maxColumns);

  grid.style.gridTemplateColumns = `repeat(${finalColumns}, 1fr)`;
}

window.addEventListener('DOMContentLoaded', updateColumns);
window.addEventListener('resize', updateColumns);
