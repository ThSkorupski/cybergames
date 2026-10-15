function afficher() {
  const id = location.hash.slice(1) || 'users';
  document.querySelectorAll('.panel').forEach(p => p.hidden = (p.id !== id));
  document.querySelectorAll('.tab').forEach(t => t.classList.toggle('active', t.hash === '#' + id));
}
window.addEventListener('hashchange', afficher);
afficher();