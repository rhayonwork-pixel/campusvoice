const CV={
 categories:['Facilities','Electrical','Plumbing & Water','Cleanliness & Sanitation','IT & Internet','Safety & Security','Lost & Found','Other'],
 priorities:['Low','Medium','High','Urgent'],
 statuses:['Submitted','Under Review','Assigned','In Progress','Waiting for Resources','Resolved','Reopened','Closed','Cancelled'],
 colleges:['College of Arts and Sciences (CAS)','College of Criminal Justice Education (CCJE)','College of Business and Accountancy (CBA)','College of Accountancy (COA)','College of Computer Studies (CCS)','College of Teacher Education (CTE)'],
 departments:['College of Arts and Sciences (CAS)','College of Criminal Justice Education (CCJE)','College of Business and Accountancy (CBA)','College of Accountancy (COA)','College of Computer Studies (CCS)','College of Teacher Education (CTE)','Maintenance','IT','Security','General Services','Administration'],
 buildings:['Innovation Building','CBA Building','Admin Building','ERC Building','ADC Building'],
 floors:['Ground Floor','1st Floor','2nd Floor','3rd Floor','4th Floor','5th Floor'],
 locations:[
  {building:'Innovation Building',floor:'2nd Floor',area:'Computer Lab',status:'Active'},
  {building:'CBA Building',floor:'2nd Floor',area:'Room 204',status:'Active'},
  {building:'Admin Building',floor:'1st Floor',area:'Restroom',status:'Active'},
  {building:'ERC Building',floor:'3rd Floor',area:'Laboratory 2',status:'Active'},
  {building:'ADC Building',floor:'Ground Floor',area:'Wash Area',status:'Active'}
 ],
 concerns:[
  {id:'CV-2026-0001',student:'Juan Dela Cruz',studentId:'2026-0012',email:'student@cmu.edu.ph',title:'Broken Chair in Classroom',category:'Facilities',building:'CBA Building',floor:'2nd Floor',area:'Room 204',description:'One classroom chair has a broken leg and needs repair.',priority:'Medium',status:'In Progress',assigned:'Pedro S. Santos',department:'Maintenance',date:'2026-10-01',response:'Repair inspection is currently in progress.',action:'Inspected the chair and prepared repair materials.',updated:'2026-10-05',photo:''},
  {id:'CV-2026-0002',student:'Juan Dela Cruz',studentId:'2026-0012',email:'student@cmu.edu.ph',title:'Unclean Restroom',category:'Cleanliness & Sanitation',building:'Admin Building',floor:'1st Floor',area:'Restroom',description:'The restroom requires cleaning and replenishment of supplies.',priority:'High',status:'Under Review',assigned:'Unassigned',department:'General Services',date:'2026-10-04',response:'Your concern has been received and is currently under review by the appropriate university personnel.',action:'',updated:'2026-10-04',photo:''},
  {id:'CV-2026-0003',student:'Maria Santos',studentId:'2026-0044',email:'maria@cmu.edu.ph',title:'Broken Light',category:'Electrical',building:'ERC Building',floor:'3rd Floor',area:'Laboratory 2',description:'Several ceiling lights are not working.',priority:'High',status:'Resolved',assigned:'Ana C. Cruz',department:'Maintenance',date:'2026-09-25',response:'Your concern has been addressed and marked as resolved.',action:'Replaced two defective bulbs and tested the circuit.',updated:'2026-10-02',photo:''},
  {id:'CV-2026-0004',student:'Carla Reyes',studentId:'2026-0091',email:'carla@cmu.edu.ph',title:'Slow Internet Connection',category:'IT & Internet',building:'Innovation Building',floor:'2nd Floor',area:'Computer Lab',description:'Internet connection becomes unstable during classes.',priority:'Medium',status:'Assigned',assigned:'Miguel R. Ramos',department:'IT',date:'2026-10-03',response:'Your concern has been reviewed and assigned to the appropriate university personnel for action.',action:'',updated:'2026-10-04',photo:''},
  {id:'CV-2026-0005',student:'Leo Cruz',studentId:'2026-0110',email:'leo@cmu.edu.ph',title:'Leaking Faucet',category:'Plumbing & Water',building:'ADC Building',floor:'Ground Floor',area:'Wash Area',description:'The faucet is continuously leaking.',priority:'Low',status:'Waiting for Resources',assigned:'Pedro S. Santos',department:'Maintenance',date:'2026-09-30',response:'The repair requires replacement materials before the work can be completed.',action:'Inspected the faucet; replacement part is pending.',updated:'2026-10-03',photo:''}
 ],
 users:[
  {firstName:'Juan',middleInitial:'D.',lastName:'Dela Cruz',id:'2026-0012',email:'student@cmu.edu.ph',role:'Student',status:'Active',department:'College of Computer Studies (CCS)',profilePhoto:'',contact:''},
  {firstName:'Maria',middleInitial:'S.',lastName:'Santos',id:'2026-0044',email:'maria@cmu.edu.ph',role:'Student',status:'Active',department:'College of Arts and Sciences (CAS)',profilePhoto:'',contact:''},
  {firstName:'Pedro',middleInitial:'S.',lastName:'Santos',id:'ST-001',email:'pedro@cmu.edu.ph',role:'Staff',status:'Active',department:'Maintenance',position:'Maintenance Staff',coverage:'Facilities · Electrical · Plumbing & Water · Cleanliness & Sanitation',profilePhoto:'',contact:''},
  {firstName:'Ana',middleInitial:'C.',lastName:'Cruz',id:'ST-002',email:'ana@cmu.edu.ph',role:'Staff',status:'Active',department:'Maintenance',position:'Maintenance Staff',coverage:'Facilities · Electrical · Plumbing & Water · Cleanliness & Sanitation',profilePhoto:'',contact:''},
  {firstName:'Miguel',middleInitial:'R.',lastName:'Ramos',id:'ST-003',email:'miguel@cmu.edu.ph',role:'Staff',status:'Active',department:'IT',position:'IT Staff',coverage:'IT & Internet',profilePhoto:'',contact:''},
  {firstName:'Joy',middleInitial:'A.',lastName:'De Guzman',id:'AD-001',email:'admin@cmu.edu.ph',role:'Super Admin',status:'Active',department:'Administration',profilePhoto:'',contact:''}
 ],
 notifications:[
  {id:'N-001',title:'New Assignment',message:'CV-2026-0001 has been assigned to you.',time:'Today, 9:12 AM',role:'staff',target:'CV-2026-0001',targetStaffId:'ST-001',read:false},
  {id:'N-002',title:'Status Updated',message:'CV-2026-0003 has been marked as resolved.',time:'Oct 2, 2026',role:'student',target:'CV-2026-0003',targetStudentId:'2026-0044',read:false},
  {id:'N-003',title:'Waiting for Resources',message:'CV-2026-0005 is waiting for replacement materials.',time:'Oct 3, 2026',role:'student',target:'CV-2026-0005',targetStudentId:'2026-0110',read:true},
  {id:'N-004',title:'New Concern Submitted',message:'A new concern requires administrative review.',time:'Oct 4, 2026',role:'admin',target:'CV-2026-0002',read:false}
 ]
};
function cvStore(){localStorage.setItem('cv_demo_data',JSON.stringify(CV));}
function cvLoad(){const x=localStorage.getItem('cv_demo_data');if(x){try{const saved=JSON.parse(x);Object.assign(CV,saved);CV.users=(CV.users||[]).map(u=>{if(u.name&&!u.firstName){const parts=String(u.name).trim().split(/\s+/);u.firstName=parts.shift()||'';u.lastName=parts.length?parts.pop():'';u.middleInitial=parts.length?parts.join(' ').slice(0,4):'';delete u.name}u.profilePhoto=u.profilePhoto||'';u.contact=u.contact||'';u.department=u.department||'';return u});CV.priorities=['Low','Medium','High','Urgent'];
CV.statuses=['Submitted','Under Review','Assigned','In Progress','Waiting for Resources','Resolved','Reopened','Closed','Cancelled'];
const staffNameMap={'Pedro Santos':'Pedro S. Santos','Ana Cruz':'Ana C. Cruz','Miguel Ramos':'Miguel R. Ramos'};
CV.concerns=(CV.concerns||[]).map(c=>({...c,assigned:staffNameMap[c.assigned]||c.assigned,priority:c.priority||'Pending Assessment',photo:c.photo||'',assignmentHistory:Array.isArray(c.assignmentHistory)?c.assignmentHistory:[]}));
CV.locations=Array.isArray(CV.locations)&&CV.locations.length?CV.locations:CV.buildings.map(b=>({building:b,floor:'Ground Floor',area:'',status:'Active'}));const canonicalColleges=['College of Arts and Sciences (CAS)','College of Criminal Justice Education (CCJE)','College of Business and Accountancy (CBA)','College of Accountancy (COA)','College of Computer Studies (CCS)','College of Teacher Education (CTE)'];const workAreas=['Maintenance','IT','Security','General Services','Administration'];CV.colleges=canonicalColleges;CV.departments=[...new Set([...canonicalColleges,...workAreas,...(CV.departments||[]).filter(x=>!['College of Criminology','Other / Not Listed'].includes(x))])];cvStore()}catch(e){console.warn(e);cvStore()}}else cvStore();}
function cvSave(){cvStore()}
function cvSession(){
 const raw=localStorage.getItem('campusvoiceUser')||sessionStorage.getItem('campusvoiceUser');
 if(!raw) return null;
 try{return JSON.parse(raw)}catch(e){return null}
}
function cvCurrentRole(){
 const legacy=sessionStorage.getItem('cv_role');
 if(legacy) return legacy;
 const s=cvSession();
 return s?.role||'student';
}
function cvEmail(){
 const legacy=sessionStorage.getItem('cv_email');
 if(legacy) return legacy;
 const s=cvSession();
 return s?.email||'student@cmu.edu.ph';
}
function cvCurrentUser(){return CV.users.find(u=>u.email.toLowerCase()===cvEmail().toLowerCase()) || CV.users.find(u=>u.role===({student:'Student',staff:'Staff',admin:'Super Admin',super_admin:'Super Admin'}[cvCurrentRole()]||'Student')) || CV.users[0]}
function cvFullName(u=cvCurrentUser()){return [u.firstName,u.middleInitial,u.lastName].filter(Boolean).join(' ')}
function cvName(){return cvFullName()}
function cvInitial(){const u=cvCurrentUser();return [u.firstName,u.lastName].filter(Boolean).map(x=>x[0]).join('').toUpperCase()||'CV'}
function cvRoleLabel(){return cvCurrentRole()==='student'?'Student':cvCurrentRole()==='staff'?'Staff':'Super Admin'}
function cvFindConcern(id){return CV.concerns.find(c=>c.id===id)}
function cvPhoto(){return cvCurrentUser()?.profilePhoto||''}
function cvSetProfile(patch){const u=cvCurrentUser();Object.assign(u,patch);cvSave();}
function esc(s=''){return String(s).replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]))}
function slug(s=''){return s.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'')}
function badge(s){return `<span class="status ${slug(s)}">${esc(s)}</span>`}
function priority(s){return `<span class="priority ${slug(s)}">${esc(s)}</span>`}
function toast(msg){let t=document.querySelector('.toast');if(!t){t=document.createElement('div');t.className='toast';document.body.appendChild(t)}t.textContent=msg;t.classList.add('show');clearTimeout(window.__toast);window.__toast=setTimeout(()=>t.classList.remove('show'),2400)}
function cvReadFile(file,cb){if(!file){cb('');return}if(!file.type.startsWith('image/')){toast('Please select an image file.');cb('');return}const r=new FileReader();r.onload=()=>cb(r.result);r.readAsDataURL(file)}
function cvAddNotification(n){CV.notifications.unshift({...n,id:'N-'+Date.now(),read:false});cvSave()}
function cvOpenNotification(n){n.read=true;cvSave();if(n.target)location.href=`concern-details.html?id=${encodeURIComponent(n.target)}`;else toast('Notification marked as read.')}
cvLoad();
