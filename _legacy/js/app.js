const user = JSON.parse(
  localStorage.getItem("campusvoiceUser") ||
  sessionStorage.getItem("campusvoiceUser") ||
  "null"
);

function requireLogin(){
  if(!user) window.location.href = "../index.html";
}

function logout(){
  localStorage.removeItem("campusvoiceUser");
  sessionStorage.removeItem("campusvoiceUser");
  window.location.href = "../index.html";
}

function fillUser(){
  document.querySelectorAll("[data-user-name]").forEach(el=>el.textContent=user?.name || "CMU User");
  document.querySelectorAll("[data-user-role]").forEach(el=>el.textContent=(user?.role || "user").replace(/^./,m=>m.toUpperCase()));
  document.querySelectorAll("[data-user-initial]").forEach(el=>el.textContent=(user?.name || "C").charAt(0).toUpperCase());
}

function setupMobileMenu(){
  const button=document.querySelector(".mobile-menu");
  const sidebar=document.querySelector(".sidebar");
  if(button && sidebar) button.addEventListener("click",()=>sidebar.classList.toggle("open"));
}

function setupSearch(){
  const input=document.querySelector("[data-search]");
  const table=document.querySelector("[data-search-table]");
  if(!input || !table) return;
  input.addEventListener("input",()=>{
    const q=input.value.toLowerCase();
    table.querySelectorAll("tbody tr").forEach(row=>{
      row.style.display=row.innerText.toLowerCase().includes(q)?"":"none";
    });
  });
}

function guardRole(expected){
  requireLogin();
  if(user && user.role !== expected){
    window.location.href=`../${user.role}/dashboard.html`;
  }
}

document.addEventListener("DOMContentLoaded",()=>{
  fillUser();
  setupMobileMenu();
  setupSearch();
});
