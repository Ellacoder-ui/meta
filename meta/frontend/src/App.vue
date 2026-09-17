<script setup>
/* eslint-disable */
import { ref, reactive, computed } from 'vue'

// ═══════════════════════════════════════════
// HELPERS
// ═══════════════════════════════════════════
function today() { return new Date().toISOString().split('T')[0] }
function fmtDate(d) { return d ? new Date(d).toLocaleDateString('en-PH') : '—' }
function fmtDateTime(d) { return d ? new Date(d).toLocaleString('en-PH') : '—' }
function uid() { return Date.now() + Math.random().toString(36).slice(2) }
function padId(n, prefix) { return prefix + '-' + String(n).padStart(4, '0') }
function statusClass(s) {
  const m = { Available:'badge-ok', Active:'badge-ok', Approved:'badge-ok', Returned:'badge-ok', Completed:'badge-blue', Borrowed:'badge-royal', Pending:'badge-warn', 'Low Stock':'badge-warn', 'Under Maintenance':'badge-warn', Rejected:'badge-err', Damaged:'badge-err', Inactive:'badge-gray', Archived:'badge-gray', 'Out of Stock':'badge-err', Cancelled:'badge-gray' }
  return m[s] || 'badge-gray'
}

// ═══════════════════════════════════════════
// NAV STATE
// ═══════════════════════════════════════════
const activeView  = ref('signup')
const adminView   = ref('dashboard')
const userView    = ref('dashboard')
const currentUser = ref(null)

function goAdmin(v) { adminView.value = v }
function goUser(v)  { userView.value  = v }

function logout() {
  addAuditLog('Logout', `"${currentUser.value?.name}" logged out`)
  activeView.value = 'login'
  adminView.value  = 'dashboard'
  userView.value   = 'dashboard'
  currentUser.value = null
}

// ═══════════════════════════════════════════
// AUTH
// ═══════════════════════════════════════════
const selectedRole   = ref('user')
const signupUsername = ref('')
const signupPassword = ref('')
const signupConfirm  = ref('')
const signupError    = ref('')
const loginUsername  = ref('')
const loginPassword  = ref('')
const loginError     = ref('')

function selectRole(r) { selectedRole.value = r }

function doSignup() {
  signupError.value = ''
  if (!signupUsername.value.trim())       { signupError.value = 'Username is required'; return }
  if (signupPassword.value.length < 6)    { signupError.value = 'Password must be at least 6 characters'; return }
  if (signupPassword.value !== signupConfirm.value) { signupError.value = 'Passwords do not match'; return }
  activeView.value = 'choose-role'
}

function continueToPortal() {
  const name = signupUsername.value || (selectedRole.value === 'admin' ? 'Administrator' : 'New User')
  currentUser.value = { username: signupUsername.value, name, role: selectedRole.value }
  if (selectedRole.value === 'admin') { activeView.value = 'admin'; adminView.value = 'dashboard' }
  else { activeView.value = 'user'; userView.value = 'dashboard'; initProfile() }
  addAuditLog('Registration', `New ${selectedRole.value} account registered: ${name}`)
}

function doLogin() {
  loginError.value = ''
  if (!loginUsername.value.trim()) { loginError.value = 'Username is required'; return }
  if (!loginPassword.value)        { loginError.value = 'Password is required'; return }
  const name = selectedRole.value === 'admin' ? 'System Administrator' : loginUsername.value
  currentUser.value = { username: loginUsername.value, name, role: selectedRole.value }
  if (selectedRole.value === 'admin') { activeView.value = 'admin'; adminView.value = 'dashboard' }
  else { activeView.value = 'user'; userView.value = 'dashboard'; initProfile() }
  addAuditLog('Login', `"${loginUsername.value}" logged in as ${selectedRole.value}`)
  loginUsername.value = ''; loginPassword.value = ''
}

// ═══════════════════════════════════════════
// CATEGORIES
// ═══════════════════════════════════════════
const categories   = ref([])
const catForm      = reactive({ name:'', description:'' })
const showCatModal = ref(false)
const editingCat   = ref(null)

function openAddCat()  { catForm.name=''; catForm.description=''; editingCat.value=null; showCatModal.value=true }
function openEditCat(c){ catForm.name=c.name; catForm.description=c.description; editingCat.value=c; showCatModal.value=true }
function saveCat() {
  if (!catForm.name.trim()) return
  if (editingCat.value) { editingCat.value.name=catForm.name; editingCat.value.description=catForm.description; addAuditLog('Category',`Updated category: ${catForm.name}`) }
  else { categories.value.push({ id:uid(), name:catForm.name, description:catForm.description }); addAuditLog('Category',`Added category: ${catForm.name}`) }
  showCatModal.value=false
}
function deleteCat(c) { categories.value=categories.value.filter(x=>x.id!==c.id); addAuditLog('Category',`Deleted category: ${c.name}`) }
const catNames = computed(()=>['Select category',...categories.value.map(c=>c.name)])

// ═══════════════════════════════════════════
// DEPARTMENTS
// ═══════════════════════════════════════════
const departments    = ref([])
const deptForm       = reactive({ name:'', code:'', head:'', description:'' })
const showDeptModal  = ref(false)
const editingDept    = ref(null)

function openAddDept()  { Object.assign(deptForm,{name:'',code:'',head:'',description:''}); editingDept.value=null; showDeptModal.value=true }
function openEditDept(d){ Object.assign(deptForm,d); editingDept.value=d; showDeptModal.value=true }
function saveDept() {
  if (!deptForm.name.trim()) return
  if (editingDept.value) { Object.assign(editingDept.value,{...deptForm}); addAuditLog('Department',`Updated department: ${deptForm.name}`) }
  else { departments.value.push({ id:uid(),...deptForm }); addAuditLog('Department',`Added department: ${deptForm.name}`) }
  showDeptModal.value=false
}
function deleteDept(d) { departments.value=departments.value.filter(x=>x.id!==d.id); addAuditLog('Department',`Deleted department: ${d.name}`) }
const deptNames = computed(()=>['Select department',...departments.value.map(d=>d.name)])

// ═══════════════════════════════════════════
// SUPPLIERS
// ═══════════════════════════════════════════
const suppliers        = ref([])
const supplierForm     = reactive({ name:'',contactPerson:'',email:'',phone:'',address:'',status:'Active',notes:'' })
const showSupModal     = ref(false)
const editingSupplier  = ref(null)
const showSupDelModal  = ref(false)
const deletingSupplier = ref(null)

function openAddSupplier()  { Object.assign(supplierForm,{name:'',contactPerson:'',email:'',phone:'',address:'',status:'Active',notes:''}); editingSupplier.value=null; showSupModal.value=true }
function openEditSupplier(s){ Object.assign(supplierForm,s); editingSupplier.value=s; showSupModal.value=true }
function saveSupplier() {
  if (!supplierForm.name.trim()) return
  if (editingSupplier.value) { Object.assign(editingSupplier.value,{...supplierForm}); addAuditLog('Supplier',`Updated supplier: ${supplierForm.name}`) }
  else { suppliers.value.push({ id:uid(),...supplierForm }); addAuditLog('Supplier',`Added supplier: ${supplierForm.name}`) }
  showSupModal.value=false
}
function confirmDeleteSupplier(s){ deletingSupplier.value=s; showSupDelModal.value=true }
function doDeleteSupplier(){ suppliers.value=suppliers.value.filter(x=>x.id!==deletingSupplier.value.id); addAuditLog('Supplier',`Deleted supplier: ${deletingSupplier.value.name}`); showSupDelModal.value=false }
const supplierNames = computed(()=>['Select supplier',...suppliers.value.map(s=>s.name)])

// ═══════════════════════════════════════════
// INVENTORY
// ═══════════════════════════════════════════
const inventory       = ref([])
const invForm         = reactive({ name:'',category:'',description:'',quantity:1,status:'Available',location:'',assetTag:'',brand:'',serialNumber:'',acquisitionDate:'',notes:'' })
const invSearch       = ref('')
const invCatFilter    = ref('All Categories')
const invStatFilter   = ref('All Status')
const editingItem     = ref(null)
const selectedItem    = ref(null)
const showInvDel      = ref(false)
const deletingItem    = ref(null)
const invSaved        = ref(false)

const filteredInventory = computed(()=>inventory.value.filter(i=>{
  const ms=!invSearch.value||i.name.toLowerCase().includes(invSearch.value.toLowerCase())
  const mc=invCatFilter.value==='All Categories'||i.category===invCatFilter.value
  const ms2=invStatFilter.value==='All Status'||i.status===invStatFilter.value
  return ms&&mc&&ms2
}))

const availableItems = computed(()=>inventory.value.filter(i=>i.status==='Available'&&i.availableQty>0))
const invSelectNames = computed(()=>['Select inventory item',...availableItems.value.map(i=>`${i.name} (${i.availableQty} available)`)])

function openAddInv(){ Object.assign(invForm,{name:'',category:'',description:'',quantity:1,status:'Available',location:'',assetTag:'',brand:'',serialNumber:'',acquisitionDate:'',notes:''}); editingItem.value=null; invSaved.value=false; goAdmin('inventory-add') }
function openEditInv(item){ Object.assign(invForm,{...item,quantity:item.totalQty}); editingItem.value=item; invSaved.value=false; goAdmin('inventory-edit') }
function viewInvItem(item){ selectedItem.value=item; goAdmin('inventory-detail') }

function saveInv(){
  if(!invForm.name.trim()) return
  const qty=parseInt(invForm.quantity)||0
  if(editingItem.value){
    const used=editingItem.value.totalQty-editingItem.value.availableQty
    Object.assign(editingItem.value,{...invForm,totalQty:qty,availableQty:Math.max(0,qty-used)})
    addAuditLog('Inventory',`Updated item: ${invForm.name}`)
  } else {
    inventory.value.push({ id:uid(),...invForm,totalQty:qty,availableQty:qty,borrowedQty:0,damagedQty:0 })
    addAuditLog('Inventory',`Added inventory item: ${invForm.name}`)
  }
  invSaved.value=true; setTimeout(()=>{ invSaved.value=false; goAdmin('inventory') },1200)
}

function confirmDelInv(item){ deletingItem.value=item; showInvDel.value=true }
function doDelInv(){ inventory.value=inventory.value.filter(i=>i.id!==deletingItem.value.id); addAuditLog('Inventory',`Archived item: ${deletingItem.value.name}`); showInvDel.value=false; if(adminView.value==='inventory-detail') goAdmin('inventory') }

// ═══════════════════════════════════════════
// STOCK IN
// ═══════════════════════════════════════════
const stockInRecords  = ref([])
const stockInForm     = reactive({ itemName:'',quantity:1,supplier:'',dateReceived:today(),receivedBy:'',notes:'' })
const showSIConfirm   = ref(false)
const siSearch        = ref('')

const filteredStockIn = computed(()=>stockInRecords.value.filter(r=>!siSearch.value||r.itemName.toLowerCase().includes(siSearch.value.toLowerCase())||r.supplier?.toLowerCase().includes(siSearch.value.toLowerCase())))

function submitStockIn(){ if(!stockInForm.itemName||stockInForm.itemName==='Select inventory item') return; showSIConfirm.value=true }
function confirmStockIn(){
  const rawName = stockInForm.itemName.split(' (')[0]
  const item=inventory.value.find(i=>i.name===rawName)
  const qty=parseInt(stockInForm.quantity)||0
  if(item){ item.totalQty+=qty; item.availableQty+=qty; if(item.status==='Out of Stock') item.status='Available' }
  stockInRecords.value.unshift({ id:uid(),...stockInForm,itemName:rawName,qty,date:today() })
  addAuditLog('Stock In',`Received ${qty}x "${rawName}" from ${stockInForm.supplier||'unknown'}`)
  addAdminNotif('stock',`Stock In: ${qty} units of "${rawName}" received`)
  Object.assign(stockInForm,{itemName:'',quantity:1,supplier:'',dateReceived:today(),receivedBy:'',notes:''})
  showSIConfirm.value=false
}

// ═══════════════════════════════════════════
// STOCK OUT
// ═══════════════════════════════════════════
const stockOutRecords = ref([])
const stockOutForm    = reactive({ itemName:'',quantity:1,reason:'',department:'',date:today(),notes:'' })
const showSOConfirm   = ref(false)
const soSearch        = ref('')

const filteredStockOut = computed(()=>stockOutRecords.value.filter(r=>!soSearch.value||r.itemName.toLowerCase().includes(soSearch.value.toLowerCase())))

function submitStockOut(){ if(!stockOutForm.itemName||stockOutForm.itemName==='Select inventory item') return; showSOConfirm.value=true }
function confirmStockOut(){
  const rawName = stockOutForm.itemName.split(' (')[0]
  const item=inventory.value.find(i=>i.name===rawName)
  const qty=parseInt(stockOutForm.quantity)||0
  if(item){ item.totalQty=Math.max(0,item.totalQty-qty); item.availableQty=Math.max(0,item.availableQty-qty); if(item.availableQty===0) item.status='Out of Stock' }
  stockOutRecords.value.unshift({ id:uid(),...stockOutForm,itemName:rawName,qty,date:today() })
  addAuditLog('Stock Out',`Disbursed ${qty}x "${rawName}" — ${stockOutForm.reason}`)
  Object.assign(stockOutForm,{itemName:'',quantity:1,reason:'',department:'',date:today(),notes:''})
  showSOConfirm.value=false
}

// ═══════════════════════════════════════════
// BORROWING
// ═══════════════════════════════════════════
const borrowings   = ref([])
const borrowTab    = ref('Active')
const borrowSearch = ref('')

const filteredBorrowings = computed(()=>borrowings.value.filter(b=>{
  const mt=borrowTab.value==='All'||(borrowTab.value==='Active'&&b.status==='Borrowed')||b.status===borrowTab.value
  const ms=!borrowSearch.value||b.borrowerName.toLowerCase().includes(borrowSearch.value.toLowerCase())||b.itemName.toLowerCase().includes(borrowSearch.value.toLowerCase())
  return mt&&ms
}))

function processReturn(b){
  b.status='Returned'; b.returnDate=today()
  const item=inventory.value.find(i=>i.name===b.itemName)
  if(item){ item.availableQty=Math.min(item.totalQty,item.availableQty+1); item.borrowedQty=Math.max(0,(item.borrowedQty||0)-1); if(item.status==='Out of Stock') item.status='Available' }
  returnsHistory.value.unshift({...b,returnDate:today(),condition:'Good'})
  addAuditLog('Returns',`Returned: "${b.itemName}" from ${b.borrowerName}`)
  addAdminNotif('system',`${b.borrowerName} returned "${b.itemName}"`)
}

// ═══════════════════════════════════════════
// RETURNS (admin)
// ═══════════════════════════════════════════
const returnsHistory = ref([])
const returnsTab     = ref('Pending Return')

const pendingReturns    = computed(()=>borrowings.value.filter(b=>b.status==='Borrowed'))
const completedReturns  = computed(()=>returnsHistory.value)

// ═══════════════════════════════════════════
// REQUESTS (admin)
// ═══════════════════════════════════════════
const requests         = ref([])
const reqTab           = ref('Pending')
const reqSearch        = ref('')
const selectedRequest  = ref(null)
const showApproveModal = ref(false)
const showRejectModal  = ref(false)
const approveNote      = ref('')
const rejectReason     = ref('')
const adminReqNote     = ref('')

const filteredRequests = computed(()=>requests.value.filter(r=>{
  const mt=reqTab.value==='All'||r.status===reqTab.value
  const ms=!reqSearch.value||r.requesterName.toLowerCase().includes(reqSearch.value.toLowerCase())||r.itemName.toLowerCase().includes(reqSearch.value.toLowerCase())
  return mt&&ms
}))

function viewRequest(r){ selectedRequest.value=r; adminReqNote.value=r.adminNote||''; goAdmin('request-detail') }
function openApprove(r){ selectedRequest.value=r; approveNote.value=''; showApproveModal.value=true }
function openReject(r) { selectedRequest.value=r; rejectReason.value=''; showRejectModal.value=true }

function confirmApprove(){
  const r=selectedRequest.value; if(!r) return
  r.status='Approved'; r.adminNote=approveNote.value; r.decidedAt=new Date().toLocaleString()
  if(r.type==='Borrow'){
    const rawName=r.itemName.split(' (')[0]
    const item=inventory.value.find(i=>i.name===rawName)
    if(item&&item.availableQty>0){
      item.availableQty--; item.borrowedQty=(item.borrowedQty||0)+1
      borrowings.value.unshift({ id:uid(),borrowerName:r.requesterName,itemName:rawName,borrowDate:today(),dueDate:r.dateNeeded||today(),duration:r.duration,status:'Borrowed',returnDate:null })
    }
  }
  addAuditLog('Request',`Approved request ${r.requestId}: "${r.itemName}"`)
  addAdminNotif('request',`Request ${r.requestId} approved for ${r.requesterName}`)
  addUserNotif(r.requesterName,'request',`Your request for "${r.itemName.split(' (')[0]}" has been APPROVED. ${approveNote.value}`)
  showApproveModal.value=false
}

function confirmReject(){
  const r=selectedRequest.value; if(!r) return
  r.status='Rejected'; r.rejectReason=rejectReason.value; r.decidedAt=new Date().toLocaleString()
  addAuditLog('Request',`Rejected request ${r.requestId}: ${rejectReason.value}`)
  addUserNotif(r.requesterName,'request',`Your request for "${r.itemName.split(' (')[0]}" has been REJECTED. Reason: ${rejectReason.value}`)
  showRejectModal.value=false
}

function saveAdminNote(){ if(selectedRequest.value){ selectedRequest.value.adminNote=adminReqNote.value; addAuditLog('Request',`Added note to request ${selectedRequest.value.requestId}`) } }

// ═══════════════════════════════════════════
// USERS (admin)
// ═══════════════════════════════════════════
const users              = ref([])
const usersSearch        = ref('')
const usersRoleFilter    = ref('All Roles')
const usersStatusFilter  = ref('All Status')
const showUserModal      = ref(false)
const showDeactivateModal= ref(false)
const editingUser        = ref(null)
const selectedUser       = ref(null)
const userForm           = reactive({ name:'',username:'',email:'',idNumber:'',role:'Student',department:'',status:'Active' })
const userFormError      = ref('')

const filteredUsers = computed(()=>users.value.filter(u=>{
  const ms=!usersSearch.value||u.name.toLowerCase().includes(usersSearch.value.toLowerCase())||u.username.toLowerCase().includes(usersSearch.value.toLowerCase())
  const mr=usersRoleFilter.value==='All Roles'||u.role===usersRoleFilter.value
  const ms2=usersStatusFilter.value==='All Status'||u.status===usersStatusFilter.value
  return ms&&mr&&ms2
}))

function openAddUser() { Object.assign(userForm,{name:'',username:'',email:'',idNumber:'',role:'Student',department:'',status:'Active'}); editingUser.value=null; userFormError.value=''; showUserModal.value=true }
function openEditUser(u){ Object.assign(userForm,u); editingUser.value=u; userFormError.value=''; showUserModal.value=true }
function viewUser(u){ selectedUser.value=u; goAdmin('user-detail') }

function saveUser(){
  if(!userForm.name.trim()||!userForm.username.trim()){ userFormError.value='Name and username are required'; return }
  if(editingUser.value){ Object.assign(editingUser.value,{...userForm}); addAuditLog('Users',`Updated user: ${userForm.name}`) }
  else { users.value.push({ id:uid(),...userForm,createdAt:today(),lastLogin:'Never' }); addAuditLog('Users',`Added user: ${userForm.name} (${userForm.role})`) }
  showUserModal.value=false
}

function openDeactivate(u){ editingUser.value=u; showDeactivateModal.value=true }
function doDeactivate(){
  editingUser.value.status=editingUser.value.status==='Active'?'Inactive':'Active'
  addAuditLog('Users',`${editingUser.value.status==='Inactive'?'Deactivated':'Activated'} user: ${editingUser.value.name}`)
  showDeactivateModal.value=false
}

const userRequestCount = computed(()=> selectedUser.value ? requests.value.filter(r=>r.requesterName===selectedUser.value.name).length : 0)
const userApprovedCount = computed(()=> selectedUser.value ? requests.value.filter(r=>r.requesterName===selectedUser.value.name&&r.status==='Approved').length : 0)
const userPendingCount = computed(()=> selectedUser.value ? requests.value.filter(r=>r.requesterName===selectedUser.value.name&&r.status==='Pending').length : 0)
const userLoanCount = computed(()=> selectedUser.value ? borrowings.value.filter(b=>b.borrowerName===selectedUser.value.name&&b.status==='Borrowed').length : 0)
const userRequestHistory = computed(()=> selectedUser.value ? requests.value.filter(r=>r.requesterName===selectedUser.value.name) : [])

// ═══════════════════════════════════════════
// AUDIT LOGS
// ═══════════════════════════════════════════
const auditLogs    = ref([])
const auditSearch  = ref('')
const auditFilter  = ref('All Events')

function addAuditLog(eventType, description){
  auditLogs.value.unshift({ id:uid(), timestamp:new Date().toLocaleString('en-PH'), user:currentUser.value?.name||'System', eventType, description, ip:'192.168.1.'+Math.floor(Math.random()*50+1) })
}

const filteredAuditLogs = computed(()=>auditLogs.value.filter(l=>{
  const ms=!auditSearch.value||l.description.toLowerCase().includes(auditSearch.value.toLowerCase())||l.user.toLowerCase().includes(auditSearch.value.toLowerCase())
  const mf=auditFilter.value==='All Events'||l.eventType===auditFilter.value
  return ms&&mf
}))

// ═══════════════════════════════════════════
// NOTIFICATIONS
// ═══════════════════════════════════════════
const adminNotifs     = ref([])
const userNotifStore  = reactive({})
const adminNotifTab   = ref('All')

function addAdminNotif(type, message){ adminNotifs.value.unshift({ id:uid(),type,message,read:false,createdAt:new Date().toLocaleString('en-PH') }) }
function addUserNotif(username, type, message){
  if(!userNotifStore[username]) userNotifStore[username]=[]
  userNotifStore[username].unshift({ id:uid(),type,message,read:false,createdAt:new Date().toLocaleString('en-PH') })
}

const currentUserNotifs = computed(()=>userNotifStore[currentUser.value?.name]||[])
const userNotifTab = ref('All')

const filteredAdminNotifs = computed(()=>adminNotifs.value.filter(n=>{
  if(adminNotifTab.value==='Unread') return !n.read
  if(adminNotifTab.value==='All') return true
  return n.type===adminNotifTab.value.toLowerCase()
}))
const filteredUserNotifs = computed(()=>currentUserNotifs.value.filter(n=>{
  if(userNotifTab.value==='Unread') return !n.read
  if(userNotifTab.value==='All') return true
  return n.type===userNotifTab.value.toLowerCase()
}))

function markAllAdminRead(){ adminNotifs.value.forEach(n=>n.read=true) }
function markAllUserRead(){ currentUserNotifs.value.forEach(n=>n.read=true) }
function markAdminNotifRead(n){ n.read=true }
function markUserNotifRead(n){ n.read=true }

const unreadAdminCount = computed(()=>adminNotifs.value.filter(n=>!n.read).length)
const unreadUserCount  = computed(()=>currentUserNotifs.value.filter(n=>!n.read).length)

// ═══════════════════════════════════════════
// REPORTS
// ═══════════════════════════════════════════
const reportType      = ref('Inventory Report')
const reportFrom      = ref('')
const reportTo        = ref('')
const reportCat       = ref('All Categories')
const reportGenerated = ref(false)
const reportTypes     = ['Inventory Report','Borrowing Report','Request Report','Stock Movement','User Activity']

function generateReport(){ reportGenerated.value=true; addAuditLog('Reports',`Generated: ${reportType.value}`) }
function selectReportType(t){ reportType.value=t; reportGenerated.value=false }

const reportData = computed(()=>{
  if(!reportGenerated.value) return []
  if(reportType.value==='Inventory Report') return inventory.value.filter(i=>reportCat.value==='All Categories'||i.category===reportCat.value)
  if(reportType.value==='Borrowing Report') return borrowings.value
  if(reportType.value==='Request Report')   return requests.value
  if(reportType.value==='Stock Movement')   return [...stockInRecords.value.map(r=>({...r,direction:'IN'})),...stockOutRecords.value.map(r=>({...r,direction:'OUT'}))].sort((a,b)=>b.id-a.id)
  if(reportType.value==='User Activity')    return auditLogs.value
  return []
})

// ═══════════════════════════════════════════
// SETTINGS
// ═══════════════════════════════════════════
const settings = reactive({
  systemName:'TechVault: ICT Inventory Platform', institution:'Liceo de Cagayan University',
  department:'Information Technology Department', email:'it@lcu.edu.ph',
  allowRequests:true, requireApproval:true, autoArchive:false,
  maxBorrow:'1 week', lowStockThreshold:'5 units',
  emailNotif:true, notifyAdmin:true, notifyUsers:true, returnReminders:false, lowStockAlerts:false,
  twoFactor:false, selfReg:false, maintenance:false,
  sessionTimeout:'1 hour', passwordPolicy:'Standard (8+ characters)',
  requestFormat:'REQ-YYYY-####', terms:''
})
const settingsSaved = ref(false)
const showResetModal = ref(false)
const showClearModal = ref(false)

function saveSettings(){ settingsSaved.value=true; addAuditLog('Settings','System settings updated'); setTimeout(()=>settingsSaved.value=false,3000) }
function doResetSettings(){ Object.assign(settings,{ systemName:'TechVault: ICT Inventory Platform', institution:'Liceo de Cagayan University', department:'Information Technology Department', email:'it@lcu.edu.ph', allowRequests:true, requireApproval:true, autoArchive:false, maxBorrow:'1 week', lowStockThreshold:'5 units', emailNotif:true, notifyAdmin:true, notifyUsers:true, returnReminders:false, lowStockAlerts:false, twoFactor:false, selfReg:false, maintenance:false, sessionTimeout:'1 hour', passwordPolicy:'Standard (8+ characters)', requestFormat:'REQ-YYYY-####', terms:'' }); addAuditLog('Settings','Settings reset to defaults'); showResetModal.value=false }
function doClearInventory(){ inventory.value=[]; addAuditLog('Settings','All inventory records cleared'); showClearModal.value=false }

// ═══════════════════════════════════════════
// COMPUTED ADMIN STATS
// ═══════════════════════════════════════════
const adminStats = computed(()=>({
  total:    inventory.value.reduce((s,i)=>s+(i.totalQty||0),0),
  available:inventory.value.reduce((s,i)=>s+(i.availableQty||0),0),
  lowStock: inventory.value.filter(i=>(i.availableQty||0)>0&&(i.availableQty||0)<=(parseInt(settings.lowStockThreshold)||5)).length,
  borrowed: borrowings.value.filter(b=>b.status==='Borrowed').length,
  damaged:  inventory.value.reduce((s,i)=>s+(i.damagedQty||0),0),
  pending:  requests.value.filter(r=>r.status==='Pending').length
}))

// ═══════════════════════════════════════════
// USER PORTAL STATE
// ═══════════════════════════════════════════

const uInvSearch     = ref('')
const uInvCat        = ref('All Categories')
const uInvStatus     = ref('All Status')
const uSelectedItem  = ref(null)

const filteredUserInv = computed(()=>inventory.value.filter(i=>{
  const ms=!uInvSearch.value||i.name.toLowerCase().includes(uInvSearch.value.toLowerCase())
  const mc=uInvCat.value==='All Categories'||i.category===uInvCat.value
  const ms2=uInvStatus.value==='All Status'||i.status===uInvStatus.value
  return ms&&mc&&ms2
}))

function viewUserItem(item){ uSelectedItem.value=item; goUser('inventory-detail') }

const reqItemForm  = reactive({ itemName:'',purpose:'',dateNeeded:'',duration:'Select duration',notes:'' })
const reqItemDone  = ref(false)
const reqItemError = ref('')

function submitRequestItem(){
  reqItemError.value=''
  if(!reqItemForm.itemName||reqItemForm.itemName==='Select an inventory item'){ reqItemError.value='Please select an item'; return }
  if(!reqItemForm.purpose.trim()){ reqItemError.value='Please enter a purpose'; return }
  const name=reqItemForm.itemName.split(' (')[0]
  const r={ id:uid(), requestId:padId(requests.value.length+1,'REQ'), type:'Request', requesterName:currentUser.value?.name||'User', itemName:name, purpose:reqItemForm.purpose, dateNeeded:reqItemForm.dateNeeded, duration:reqItemForm.duration, notes:reqItemForm.notes, status:'Pending', submittedAt:new Date().toLocaleString('en-PH') }
  requests.value.unshift(r)
  addAdminNotif('request',`New request from ${r.requesterName}: "${name}"`)
  addUserNotif(currentUser.value?.name,'request',`Your request for "${name}" is pending review.`)
  addAuditLog('Request',`User submitted request for "${name}"`)
  Object.assign(reqItemForm,{itemName:'',purpose:'',dateNeeded:'',duration:'Select duration',notes:''})
  reqItemDone.value=true
}

const borrowItemForm  = reactive({ itemName:'',borrowDate:today(),returnDate:'',duration:'Select duration',purpose:'',notes:'',agreed:false })
const borrowItemDone  = ref(false)
const borrowItemError = ref('')

function submitBorrowItem(){
  borrowItemError.value=''
  if(!borrowItemForm.itemName||borrowItemForm.itemName==='Select available equipment'){ borrowItemError.value='Please select equipment'; return }
  if(!borrowItemForm.purpose.trim()){ borrowItemError.value='Please enter a purpose'; return }
  if(!borrowItemForm.agreed){ borrowItemError.value='Please agree to the terms'; return }
  const name=borrowItemForm.itemName.split(' (')[0]
  const r={ id:uid(), requestId:padId(requests.value.length+1,'BRW'), type:'Borrow', requesterName:currentUser.value?.name||'User', itemName:name, purpose:borrowItemForm.purpose, dateNeeded:borrowItemForm.borrowDate, returnDate:borrowItemForm.returnDate, duration:borrowItemForm.duration, notes:borrowItemForm.notes, status:'Pending', submittedAt:new Date().toLocaleString('en-PH') }
  requests.value.unshift(r)
  addAdminNotif('request',`New borrow request from ${r.requesterName}: "${name}"`)
  addUserNotif(currentUser.value?.name,'request',`Your borrow request for "${name}" is pending review.`)
  addAuditLog('Request',`User submitted borrow request for "${name}"`)
  Object.assign(borrowItemForm,{itemName:'',borrowDate:today(),returnDate:'',duration:'Select duration',purpose:'',notes:'',agreed:false})
  borrowItemDone.value=true
}

const myReqTab    = ref('All')
const myReqSearch = ref('')
const selectedMyReq = ref(null)

const myRequests = computed(()=>requests.value.filter(r=>{
  const mine=r.requesterName===(currentUser.value?.name||'User')
  const mt=myReqTab.value==='All'||r.status===myReqTab.value
  const ms=!myReqSearch.value||r.itemName.toLowerCase().includes(myReqSearch.value.toLowerCase())
  return mine&&mt&&ms
}))

function viewMyRequest(r){ selectedMyReq.value=r; goUser('request-detail') }
function cancelMyRequest(r){ r.status='Cancelled'; addAuditLog('Request',`User cancelled request ${r.requestId}`); addAdminNotif('request',`Request ${r.requestId} cancelled by ${r.requesterName}`) }

const myBorTab = ref('Active')
const myBorrows = computed(()=>borrowings.value.filter(b=>{
  const mine=b.borrowerName===(currentUser.value?.name||'User')
  const mt=myBorTab.value==='All'||(myBorTab.value==='Active'&&b.status==='Borrowed')||b.status===myBorTab.value
  return mine&&mt
}))

const uReturnsTab     = ref('Pending Return')
const myPendingReturn = computed(()=>borrowings.value.filter(b=>b.borrowerName===(currentUser.value?.name||'User')&&b.status==='Borrowed'))
const myReturnHistory = computed(()=>returnsHistory.value.filter(r=>r.borrowerName===(currentUser.value?.name||'User')))

function userSubmitReturn(b){
  processReturn(b)
  addUserNotif(currentUser.value?.name,'system',`You returned "${b.itemName}" successfully.`)
}

const userStats = computed(()=>({
  available: inventory.value.reduce((s,i)=>s+(i.availableQty||0),0),
  pending:   myRequests.value.filter(r=>r.status==='Pending').length,
  approved:  myRequests.value.filter(r=>r.status==='Approved').length,
  borrowed:  myBorrows.value.filter(b=>b.status==='Borrowed').length
}))

const profileForm = reactive({ name:'', idNumber:'', email:'', department:'' })
const passForm    = reactive({ current:'', newPass:'', confirm:'' })
const profileSaved= ref(false)
const passSaved   = ref(false)
const passError   = ref('')
const userPrefs   = reactive({ requestUpdates:true, systemAnnouncements:true, returnReminders:false })

function initProfile(){
  profileForm.name=currentUser.value?.name||''
  profileForm.idNumber=''; profileForm.email=''; profileForm.department=''
}
function saveProfile(){
  if(currentUser.value) currentUser.value.name=profileForm.name
  profileSaved.value=true
  addAuditLog('Profile','User updated profile information')
  setTimeout(()=>profileSaved.value=false,3000)
}
function savePassword(){
  passError.value=''
  if(!passForm.current){ passError.value='Enter your current password'; return }
  if(passForm.newPass.length<6){ passError.value='New password must be at least 6 characters'; return }
  if(passForm.newPass!==passForm.confirm){ passError.value='Passwords do not match'; return }
  passSaved.value=true
  addAuditLog('Profile','User changed password')
  Object.assign(passForm,{current:'',newPass:'',confirm:''})
  setTimeout(()=>passSaved.value=false,3000)
}

const recentUserActivity = computed(()=>[...myRequests.value,...myBorrows.value].slice(0,5))
</script>

<template>
<div id="tv-root">

<!-- ╔══════════════════════════════════════╗
     ║  SIGN UP                             ║
     ╚══════════════════════════════════════╝ -->
<main v-if="activeView==='signup'" class="auth-page auth-page--light">
  <aside class="auth-panel">
    <div class="logo"><div class="logo__icon">T</div><div><div class="logo__name">TechVault</div><div class="logo__sub">ICT Inventory</div></div></div>
    <div class="auth-features">
      <p class="feat-label">Platform Features</p>
      <div class="feat-item"><div class="feat-icon">🔒</div><div><div class="feat-title">Secure Access</div><div class="feat-sub">Accounts are protected end-to-end</div></div></div>
      <div class="feat-item"><div class="feat-icon">📦</div><div><div class="feat-title">Inventory Visibility</div><div class="feat-sub">Browse and track all ICT assets</div></div></div>
      <div class="feat-item"><div class="feat-icon">⚡</div><div><div class="feat-title">Real-Time Updates</div><div class="feat-sub">Track request status instantly</div></div></div>
    </div>
    <p class="auth-panel__footer">© 2026 TechVault · Liceo de Cagayan University</p>
  </aside>
  <section class="auth-card-wrapper">
    <div class="auth-card">
      <div class="auth-card__logo"><div class="logo"><div class="logo__icon">T</div><div><div class="logo__name logo__name--dark">TechVault</div><div class="logo__sub">ICT Inventory</div></div></div></div>
      <div class="badge"><span class="badge__dot"></span>Create Account</div>
      <h1 class="auth-card__title">Sign Up</h1>
      <p class="auth-card__sub">Create your TechVault account to get started.</p>
      <div class="auth-divider"></div>
      <div v-if="signupError" class="alert alert--err">{{ signupError }}</div>
      <form class="auth-form" @submit.prevent="doSignup">
        <div class="field"><label class="field__label">Username <span class="req">*</span></label><input class="field__input" v-model="signupUsername" type="text" placeholder="Choose a username"/></div>
        <div class="field"><label class="field__label">Password <span class="req">*</span></label><input class="field__input" v-model="signupPassword" type="password" placeholder="Create a password (min 6 chars)"/></div>
        <div class="field"><label class="field__label">Confirm Password <span class="req">*</span></label><input class="field__input" v-model="signupConfirm" type="password" placeholder="Repeat your password"/></div>
        <button type="submit" class="btn btn--primary btn--full">Sign Up</button>
      </form>
      <p class="auth-card__switch">Already have an account? <button class="auth-link" @click="activeView='login'; signupError=''">Log In</button></p>
    </div>
  </section>
</main>

<!-- ╔══════════════════════════════════════╗
     ║  CHOOSE ROLE                         ║
     ╚══════════════════════════════════════╝ -->
<main v-else-if="activeView==='choose-role'" class="choose-role-page">
  <div class="choose-role__inner">
    <div class="logo mb-32"><div class="logo__icon">T</div><div><div class="logo__name logo__name--dark">TechVault</div><div class="logo__sub">ICT Inventory</div></div></div>
    <div class="badge"><span class="badge__dot"></span>Account Setup</div>
    <h1 class="choose-role__title">Choose Your Role</h1>
    <p class="choose-role__subtitle">Select the role that best describes how you will use TechVault.</p>
    <div class="role-cards">
      <div class="role-card" :class="{'role-card--active':selectedRole==='admin'}" @click="selectRole('admin')">
        <div class="role-card__icon">🛡️</div>
        <h2 class="role-card__title">Admin</h2>
        <p class="role-card__desc">Manage inventory, users, transactions, reports, and system settings.</p>
        <div class="role-radio" :class="{'role-radio--on':selectedRole==='admin'}"><span v-if="selectedRole==='admin'" class="role-radio__dot"></span></div>
      </div>
      <div class="role-card" :class="{'role-card--active':selectedRole==='user'}" @click="selectRole('user')">
        <div class="role-card__icon">👤</div>
        <h2 class="role-card__title">User</h2>
        <p class="role-card__desc">View inventory, submit requests, borrow equipment, and manage personal transactions.</p>
        <div class="role-radio" :class="{'role-radio--on':selectedRole==='user'}"><span v-if="selectedRole==='user'" class="role-radio__dot"></span></div>
      </div>
    </div>
    <button class="btn btn--primary btn--continue" @click="continueToPortal">Continue →</button>
    <p class="choose-role__hint">You can contact the IT Department to change your role after registration.</p>
  </div>
</main>

<!-- ╔══════════════════════════════════════╗
     ║  LOGIN                               ║
     ╚══════════════════════════════════════╝ -->
<main v-else-if="activeView==='login'" class="auth-page auth-page--dark">
  <aside class="auth-panel auth-panel--navy">
    <div class="logo"><div class="logo__icon">T</div><div><div class="logo__name logo__name--white">TechVault</div><div class="logo__sub logo__sub--white">ICT Inventory</div></div></div>
    <div class="auth-features">
      <p class="feat-label feat-label--dim">Platform Features</p>
      <div class="feat-item"><div class="feat-icon">🛡️</div><div><div class="feat-title feat-title--white">Role-Based Access</div><div class="feat-sub feat-sub--dim">System identifies your role automatically</div></div></div>
      <div class="feat-item"><div class="feat-icon">🔍</div><div><div class="feat-title feat-title--white">Session Monitoring</div><div class="feat-sub feat-sub--dim">All sessions are securely tracked</div></div></div>
      <div class="feat-item"><div class="feat-icon">⚙️</div><div><div class="feat-title feat-title--white">Full Platform Access</div><div class="feat-sub feat-sub--dim">Admin or User dashboard upon login</div></div></div>
    </div>
    <p class="auth-panel__footer auth-panel__footer--dim">© 2026 TechVault · Liceo de Cagayan University</p>
  </aside>
  <section class="auth-card-wrapper">
    <div class="auth-card auth-card--dark">
      <div class="auth-card__logo"><div class="logo"><div class="logo__icon">T</div><div><div class="logo__name logo__name--white">TechVault</div><div class="logo__sub logo__sub--white">ICT Inventory</div></div></div></div>
      <div class="badge badge--sky"><span class="badge__dot badge__dot--sky"></span>Sign In</div>
      <h1 class="auth-card__title auth-card__title--white">Log In</h1>
      <p class="auth-card__sub auth-card__sub--dim">Your role will be identified automatically.</p>
      <div class="auth-divider auth-divider--dark"></div>
      <div v-if="loginError" class="alert alert--err-dark">{{ loginError }}</div>
      <form class="auth-form" @submit.prevent="doLogin">
        <div class="field"><label class="field__label field__label--white">Username</label><input class="field__input field__input--dark" v-model="loginUsername" type="text" placeholder="Enter your username"/></div>
        <div class="field"><label class="field__label field__label--white">Password</label><input class="field__input field__input--dark" v-model="loginPassword" type="password" placeholder="Enter your password"/></div>
        <div class="proto-box">
          <p class="proto-box__label">Prototype: Simulate saved account role</p>
          <div class="proto-box__btns">
            <button type="button" class="proto-btn" :class="{'proto-btn--on':selectedRole==='user'}" @click="selectRole('user')">User</button>
            <button type="button" class="proto-btn" :class="{'proto-btn--on':selectedRole==='admin'}" @click="selectRole('admin')">Administrator</button>
          </div>
        </div>
        <button type="submit" class="btn btn--sky btn--full">Log In</button>
      </form>
      <p class="auth-card__switch auth-card__switch--dim">Don't have an account? <button class="auth-link auth-link--sky" @click="activeView='signup'; loginError=''">Sign Up</button></p>
    </div>
  </section>
</main>

<!-- ╔══════════════════════════════════════════════════════════════╗
     ║  ADMIN PORTAL                                               ║
     ╚══════════════════════════════════════════════════════════════╝ -->
<div v-else-if="activeView==='admin'" class="portal portal--admin">
  <aside class="sidebar sidebar--admin">
    <div class="sidebar__brand">
      <div class="logo"><div class="logo__icon">T</div><div><div class="logo__name logo__name--white">TechVault</div><div class="logo__sub logo__sub--white">ICT Inventory</div></div></div>
      <span class="role-pill">● Administrator</span>
    </div>
    <nav class="sidebar__nav">
      <span class="nav-label">Management</span>
      <button class="nav-link" :class="{'nav-link--on':adminView==='dashboard'}" @click="goAdmin('dashboard')">🏠 Dashboard</button>
      <button class="nav-link" :class="{'nav-link--on':adminView.startsWith('inventory')}" @click="goAdmin('inventory')">📦 Inventory</button>
      <button class="nav-link" :class="{'nav-link--on':adminView==='stock-in'}" @click="goAdmin('stock-in')">⬇️ Stock In</button>
      <button class="nav-link" :class="{'nav-link--on':adminView==='stock-out'}" @click="goAdmin('stock-out')">⬆️ Stock Out</button>
      <button class="nav-link" :class="{'nav-link--on':adminView==='borrowing'}" @click="goAdmin('borrowing')">🤲 Borrowing</button>
      <button class="nav-link" :class="{'nav-link--on':adminView==='returns'}" @click="goAdmin('returns')">↩️ Returns</button>
      <button class="nav-link" :class="{'nav-link--on':adminView.startsWith('request')}" @click="goAdmin('requests')">
        📋 Requests <span v-if="adminStats.pending>0" class="nav-badge">{{ adminStats.pending }}</span>
      </button>
      <button class="nav-link" :class="{'nav-link--on':adminView.startsWith('user')}" @click="goAdmin('users')">👥 Users</button>
      <button class="nav-link" :class="{'nav-link--on':adminView==='suppliers'}" @click="goAdmin('suppliers')">🚚 Suppliers</button>
      <button class="nav-link" :class="{'nav-link--on':adminView==='departments'}" @click="goAdmin('departments')">🏢 Departments</button>
      <div class="nav-divider"></div>
      <span class="nav-label">System</span>
      <button class="nav-link" :class="{'nav-link--on':adminView==='categories'}" @click="goAdmin('categories')">🗂️ Categories</button>
      <button class="nav-link" :class="{'nav-link--on':adminView==='reports'}" @click="goAdmin('reports')">📊 Reports</button>
      <button class="nav-link" :class="{'nav-link--on':adminView==='audit-logs'}" @click="goAdmin('audit-logs')">📝 Audit Logs</button>
      <button class="nav-link" :class="{'nav-link--on':adminView==='notifications'}" @click="goAdmin('notifications')">
        🔔 Notifications <span v-if="unreadAdminCount>0" class="nav-badge">{{ unreadAdminCount }}</span>
      </button>
      <button class="nav-link" :class="{'nav-link--on':adminView==='settings'}" @click="goAdmin('settings')">⚙️ Settings</button>
    </nav>
    <button class="sidebar__logout" @click="logout">↪ Logout</button>
  </aside>

  <main class="portal__main">

    <!-- ── ADMIN DASHBOARD ── -->
    <div v-if="adminView==='dashboard'">
      <div class="page-header">
        <div><h1 class="page-title">Administrator Dashboard</h1><p class="page-sub">TechVault ICT Inventory Management System</p></div>
        <div class="flex-row gap-10">
          <button class="btn btn--ghost btn--sm" @click="goAdmin('reports')">📊 Reports</button>
          <button class="btn btn--primary btn--sm" @click="openAddInv">+ Add Item</button>
        </div>
      </div>
      <div class="stat-grid stat-grid--3">
        <div class="stat-card stat-card--blue"><div class="stat-val">{{ adminStats.total }}</div><div class="stat-label">Total Inventory Items</div><div class="stat-sub">All registered assets</div></div>
        <div class="stat-card stat-card--green"><div class="stat-val">{{ adminStats.available }}</div><div class="stat-label">Available Items</div><div class="stat-sub">Ready to borrow or issue</div></div>
        <div class="stat-card stat-card--warn"><div class="stat-val">{{ adminStats.lowStock }}</div><div class="stat-label">Low Stock Items</div><div class="stat-sub">Below threshold</div></div>
        <div class="stat-card stat-card--royal"><div class="stat-val">{{ adminStats.borrowed }}</div><div class="stat-label">Borrowed Items</div><div class="stat-sub">Currently checked out</div></div>
        <div class="stat-card stat-card--red"><div class="stat-val">{{ adminStats.damaged }}</div><div class="stat-label">Damaged Items</div><div class="stat-sub">Flagged assets</div></div>
        <div class="stat-card stat-card--purple"><div class="stat-val">{{ adminStats.pending }}</div><div class="stat-label">Pending Requests</div><div class="stat-sub">Awaiting your review</div></div>
      </div>
      <div class="two-col">
        <div class="scard">
          <div class="scard__hd"><span class="scard__title">Recent Requests</span><button class="btn btn--ghost btn--sm" @click="goAdmin('requests')">View All</button></div>
          <div v-if="requests.length===0" class="empty"><div class="empty__icon">📋</div><div class="empty__title">No requests yet</div><p class="empty__desc">Equipment requests will appear here once users begin submitting.</p></div>
          <table v-else class="tbl">
            <thead><tr><th>Requester</th><th>Item</th><th>Type</th><th>Status</th></tr></thead>
            <tbody>
              <tr v-for="r in requests.slice(0,5)" :key="r.id">
                <td>{{ r.requesterName }}</td><td>{{ r.itemName }}</td><td>{{ r.type }}</td>
                <td><span class="badge-pill" :class="statusClass(r.status)">{{ r.status }}</span></td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="scard">
          <div class="scard__hd"><span class="scard__title">Recent Borrowings</span><button class="btn btn--ghost btn--sm" @click="goAdmin('borrowing')">View All</button></div>
          <div v-if="borrowings.length===0" class="empty"><div class="empty__icon">🤲</div><div class="empty__title">No borrowing records</div><p class="empty__desc">Active borrowings will appear here.</p></div>
          <table v-else class="tbl">
            <thead><tr><th>Borrower</th><th>Item</th><th>Due Date</th><th>Status</th></tr></thead>
            <tbody>
              <tr v-for="b in borrowings.slice(0,5)" :key="b.id">
                <td>{{ b.borrowerName }}</td><td>{{ b.itemName }}</td><td>{{ fmtDate(b.dueDate) }}</td>
                <td><span class="badge-pill" :class="statusClass(b.status)">{{ b.status }}</span></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ── INVENTORY LIST ── -->
    <div v-else-if="adminView==='inventory'">
      <div class="page-header">
        <div><h1 class="page-title">Inventory Management</h1><p class="page-sub">{{ inventory.length }} total item types registered</p></div>
        <button class="btn btn--primary btn--sm" @click="openAddInv">+ Add Inventory Item</button>
      </div>
      <div class="scard mb-20">
        <div class="filter-bar">
          <div class="search-wrap"><span class="search-icon">🔍</span><input class="search-input" v-model="invSearch" type="text" placeholder="Search inventory..."/></div>
          <select class="sel" v-model="invCatFilter"><option>All Categories</option><option v-for="c in categories" :key="c.id">{{ c.name }}</option><option>Computers & Laptops</option><option>Networking Equipment</option><option>Peripherals</option><option>Audio/Visual</option><option>Storage Devices</option></select>
          <select class="sel" v-model="invStatFilter"><option>All Status</option><option>Available</option><option>Borrowed</option><option>Under Maintenance</option><option>Low Stock</option><option>Out of Stock</option></select>
        </div>
      </div>
      <div class="scard">
        <table class="tbl">
          <thead><tr><th>Item Name</th><th>Category</th><th>Location</th><th>Total Qty</th><th>Available</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
            <tr v-if="filteredInventory.length===0"><td colspan="7"><div class="empty"><div class="empty__icon">📦</div><div class="empty__title">No inventory records yet</div><p class="empty__desc">Use Add Inventory Item to create your first record.</p><button class="btn btn--primary btn--sm mt-12" @click="openAddInv">+ Add First Item</button></div></td></tr>
            <tr v-for="item in filteredInventory" :key="item.id">
              <td><span class="tbl-link" @click="viewInvItem(item)">{{ item.name }}</span></td>
              <td>{{ item.category||'—' }}</td><td>{{ item.location||'—' }}</td>
              <td>{{ item.totalQty }}</td><td>{{ item.availableQty }}</td>
              <td><span class="badge-pill" :class="statusClass(item.status)">{{ item.status }}</span></td>
              <td>
                <div class="action-row">
                  <button class="btn-icon" title="View" @click="viewInvItem(item)">👁️</button>
                  <button class="btn-icon" title="Edit" @click="openEditInv(item)">✏️</button>
                  <button class="btn-icon btn-icon--red" title="Archive" @click="confirmDelInv(item)">🗑️</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <div class="tbl-foot"><span class="tbl-count">{{ filteredInventory.length }} record(s)</span><div class="tbl-pages"><button class="pg-btn">←</button><button class="pg-btn pg-btn--on">1</button><button class="pg-btn">→</button></div></div>
      </div>
    </div>

    <!-- ── INVENTORY ADD / EDIT ── -->
    <div v-else-if="adminView==='inventory-add'||adminView==='inventory-edit'">
      <div class="page-header">
        <div class="page-header__back">
          <button class="back-btn" @click="goAdmin('inventory')">←</button>
          <div><h1 class="page-title">{{ adminView==='inventory-edit'?'Edit Inventory Item':'Add Inventory Item' }}</h1><p class="page-sub">{{ adminView==='inventory-edit'?'Update the equipment record':'Create a new ICT asset record' }}</p></div>
        </div>
        <div class="flex-row gap-10">
          <button class="btn btn--secondary btn--sm" @click="goAdmin('inventory')">Cancel</button>
          <button class="btn btn--primary btn--sm" @click="saveInv">✓ {{ adminView==='inventory-edit'?'Save Changes':'Add Item' }}</button>
        </div>
      </div>
      <div v-if="invSaved" class="alert alert--ok mb-20">✓ Item saved successfully! Redirecting...</div>
      <div class="two-col">
        <div class="scard p-24">
          <div class="scard__title mb-16">Basic Information</div>
          <div class="form-stack">
            <div class="field"><label class="field__label">Item Name <span class="req">*</span></label><input class="field__input" v-model="invForm.name" type="text" placeholder="Enter item name"/></div>
            <div class="field"><label class="field__label">Category <span class="req">*</span></label>
              <select class="field__input" v-model="invForm.category">
                <option value="">Select category</option>
                <option v-for="c in categories" :key="c.id">{{ c.name }}</option>
                <option>Computers & Laptops</option><option>Networking Equipment</option><option>Peripherals</option><option>Audio/Visual</option><option>Storage Devices</option><option>Mobile Devices</option>
              </select>
            </div>
            <div class="field"><label class="field__label">Description</label><textarea class="field__input field__textarea" v-model="invForm.description" placeholder="Describe the item and its intended use..."></textarea></div>
            <div class="two-col-sm">
              <div class="field"><label class="field__label">Quantity <span class="req">*</span></label><input class="field__input" v-model="invForm.quantity" type="number" min="0"/></div>
              <div class="field"><label class="field__label">Status</label><select class="field__input" v-model="invForm.status"><option>Available</option><option>Under Maintenance</option><option>Archived</option><option>Out of Stock</option></select></div>
            </div>
          </div>
        </div>
        <div class="scard p-24">
          <div class="scard__title mb-16">Additional Details</div>
          <div class="form-stack">
            <div class="field"><label class="field__label">Location / Room</label><input class="field__input" v-model="invForm.location" type="text" placeholder="e.g. IT Lab 1, Storage Room"/></div>
            <div class="field"><label class="field__label">Asset Tag / Code</label><input class="field__input" v-model="invForm.assetTag" type="text" placeholder="e.g. ICT-2024-001"/></div>
            <div class="field"><label class="field__label">Brand / Model</label><input class="field__input" v-model="invForm.brand" type="text" placeholder="Manufacturer and model"/></div>
            <div class="field"><label class="field__label">Serial Number</label><input class="field__input" v-model="invForm.serialNumber" type="text" placeholder="Manufacturer serial number"/></div>
            <div class="field"><label class="field__label">Acquisition Date</label><input class="field__input" v-model="invForm.acquisitionDate" type="date"/></div>
            <div class="field"><label class="field__label">Notes</label><textarea class="field__input field__textarea" v-model="invForm.notes" placeholder="Additional notes..." rows="2"></textarea></div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── INVENTORY DETAIL ── -->
    <div v-else-if="adminView==='inventory-detail'&&selectedItem">
      <div class="page-header">
        <div class="page-header__back">
          <button class="back-btn" @click="goAdmin('inventory')">←</button>
          <div><h1 class="page-title">{{ selectedItem.name }}</h1><p class="page-sub">Item Details</p></div>
        </div>
        <div class="flex-row gap-10">
          <button class="btn btn--secondary btn--sm" @click="confirmDelInv(selectedItem)">🗑️ Archive</button>
          <button class="btn btn--primary btn--sm" @click="openEditInv(selectedItem)">✏️ Edit Item</button>
        </div>
      </div>
      <div class="two-col-sidebar">
        <div class="form-stack">
          <div class="scard p-24">
            <div class="scard__title mb-16">Equipment Information</div>
            <div class="detail-grid">
              <div class="detail-field"><div class="detail-label">Item Name</div><div class="detail-val">{{ selectedItem.name }}</div></div>
              <div class="detail-field"><div class="detail-label">Category</div><div class="detail-val">{{ selectedItem.category||'—' }}</div></div>
              <div class="detail-field"><div class="detail-label">Asset Tag</div><div class="detail-val">{{ selectedItem.assetTag||'—' }}</div></div>
              <div class="detail-field"><div class="detail-label">Brand / Model</div><div class="detail-val">{{ selectedItem.brand||'—' }}</div></div>
              <div class="detail-field"><div class="detail-label">Serial Number</div><div class="detail-val">{{ selectedItem.serialNumber||'—' }}</div></div>
              <div class="detail-field"><div class="detail-label">Location</div><div class="detail-val">{{ selectedItem.location||'—' }}</div></div>
              <div class="detail-field"><div class="detail-label">Acquisition Date</div><div class="detail-val">{{ fmtDate(selectedItem.acquisitionDate) }}</div></div>
              <div class="detail-field"><div class="detail-label">Status</div><div class="detail-val"><span class="badge-pill" :class="statusClass(selectedItem.status)">{{ selectedItem.status }}</span></div></div>
            </div>
            <div v-if="selectedItem.description" class="mt-16">
              <div class="detail-label mb-6">Description</div>
              <p style="font-size:13px;color:#5D728A;line-height:1.7">{{ selectedItem.description }}</p>
            </div>
          </div>
          <div class="scard">
            <div class="scard__hd"><span class="scard__title">Borrow History</span></div>
            <div v-if="borrowings.filter(b=>b.itemName===selectedItem.name).length===0" class="empty"><div class="empty__icon">📋</div><div class="empty__title">No borrow history</div><p class="empty__desc">Borrowing activity for this item will appear here.</p></div>
            <table v-else class="tbl">
              <thead><tr><th>Borrower</th><th>Borrow Date</th><th>Due Date</th><th>Status</th></tr></thead>
              <tbody>
                <tr v-for="b in borrowings.filter(b=>b.itemName===selectedItem.name)" :key="b.id">
                  <td>{{ b.borrowerName }}</td><td>{{ fmtDate(b.borrowDate) }}</td><td>{{ fmtDate(b.dueDate) }}</td>
                  <td><span class="badge-pill" :class="statusClass(b.status)">{{ b.status }}</span></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="form-stack">
          <div class="scard p-24" style="text-align:center">
            <div style="width:64px;height:64px;border-radius:14px;background:#EEF7FF;border:1px solid #D6E6F8;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:28px">📦</div>
            <div style="font-size:15px;font-weight:700;color:#032B5B;margin-bottom:8px">{{ selectedItem.name }}</div>
            <div><span class="badge-pill" :class="statusClass(selectedItem.status)">{{ selectedItem.status }}</span></div>
          </div>
          <div class="scard p-24">
            <div class="scard__title mb-12">Inventory Counts</div>
            <div class="summary-rows">
              <div class="summary-row"><span class="summary-row__label">Total Quantity</span><span class="summary-row__val">{{ selectedItem.totalQty }}</span></div>
              <div class="summary-row"><span class="summary-row__label">Available</span><span class="summary-row__val" style="color:#3CB371">{{ selectedItem.availableQty }}</span></div>
              <div class="summary-row"><span class="summary-row__label">Borrowed</span><span class="summary-row__val" style="color:#1678D8">{{ selectedItem.borrowedQty||0 }}</span></div>
              <div class="summary-row"><span class="summary-row__label">Damaged</span><span class="summary-row__val" style="color:#E74C3C">{{ selectedItem.damagedQty||0 }}</span></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── STOCK IN ── -->
    <div v-else-if="adminView==='stock-in'">
      <div class="page-header"><div><h1 class="page-title">Stock In</h1><p class="page-sub">Record incoming inventory items</p></div></div>
      <div class="two-col-sidebar">
        <div class="scard">
          <div class="filter-bar" style="padding:14px 20px"><div class="search-wrap"><span class="search-icon">🔍</span><input class="search-input" v-model="siSearch" placeholder="Search records..."/></div></div>
          <table class="tbl">
            <thead><tr><th>Item</th><th>Qty</th><th>Supplier</th><th>Date Received</th><th>Received By</th><th>Notes</th></tr></thead>
            <tbody>
              <tr v-if="filteredStockIn.length===0"><td colspan="6"><div class="empty"><div class="empty__icon">⬇️</div><div class="empty__title">No stock in records</div><p class="empty__desc">Use the form to record incoming inventory.</p></div></td></tr>
              <tr v-for="r in filteredStockIn" :key="r.id">
                <td>{{ r.itemName }}</td><td>{{ r.qty }}</td><td>{{ r.supplier||'—' }}</td><td>{{ fmtDate(r.dateReceived) }}</td><td>{{ r.receivedBy||'—' }}</td><td>{{ r.notes||'—' }}</td>
              </tr>
            </tbody>
          </table>
          <div class="tbl-foot"><span class="tbl-count">{{ filteredStockIn.length }} record(s)</span></div>
        </div>
        <div class="scard p-24">
          <div class="scard__title mb-16">Record Stock In</div>
          <div class="form-stack">
            <div class="field"><label class="field__label">Item <span class="req">*</span></label>
              <select class="field__input" v-model="stockInForm.itemName">
                <option>Select inventory item</option>
                <option v-for="i in inventory" :key="i.id">{{ i.name }} ({{ i.availableQty }} available)</option>
              </select>
            </div>
            <div class="field"><label class="field__label">Quantity Received <span class="req">*</span></label><input class="field__input" v-model="stockInForm.quantity" type="number" min="1"/></div>
            <div class="field"><label class="field__label">Supplier</label>
              <select class="field__input" v-model="stockInForm.supplier">
                <option value="">Select supplier</option>
                <option v-for="s in suppliers" :key="s.id">{{ s.name }}</option>
              </select>
            </div>
            <div class="field"><label class="field__label">Date Received</label><input class="field__input" v-model="stockInForm.dateReceived" type="date"/></div>
            <div class="field"><label class="field__label">Received By</label><input class="field__input" v-model="stockInForm.receivedBy" type="text" placeholder="Staff name"/></div>
            <div class="field"><label class="field__label">Notes</label><textarea class="field__input field__textarea" v-model="stockInForm.notes" placeholder="Delivery or receipt notes..." rows="2"></textarea></div>
            <button class="btn btn--primary btn--full" @click="submitStockIn">Record Stock In</button>
          </div>
        </div>
      </div>
    </div>

    <!-- ── STOCK OUT ── -->
    <div v-else-if="adminView==='stock-out'">
      <div class="page-header"><div><h1 class="page-title">Stock Out</h1><p class="page-sub">Record outgoing or disbursed inventory</p></div></div>
      <div class="two-col-sidebar">
        <div class="scard">
          <div class="filter-bar" style="padding:14px 20px"><div class="search-wrap"><span class="search-icon">🔍</span><input class="search-input" v-model="soSearch" placeholder="Search records..."/></div></div>
          <table class="tbl">
            <thead><tr><th>Item</th><th>Qty</th><th>Reason</th><th>Department</th><th>Date</th><th>Notes</th></tr></thead>
            <tbody>
              <tr v-if="filteredStockOut.length===0"><td colspan="6"><div class="empty"><div class="empty__icon">⬆️</div><div class="empty__title">No stock out records</div><p class="empty__desc">Use the form to record outgoing inventory.</p></div></td></tr>
              <tr v-for="r in filteredStockOut" :key="r.id">
                <td>{{ r.itemName }}</td><td>{{ r.qty }}</td><td>{{ r.reason||'—' }}</td><td>{{ r.department||'—' }}</td><td>{{ fmtDate(r.date) }}</td><td>{{ r.notes||'—' }}</td>
              </tr>
            </tbody>
          </table>
          <div class="tbl-foot"><span class="tbl-count">{{ filteredStockOut.length }} record(s)</span></div>
        </div>
        <div class="scard p-24">
          <div class="scard__title mb-16">Record Stock Out</div>
          <div class="form-stack">
            <div class="field"><label class="field__label">Item <span class="req">*</span></label>
              <select class="field__input" v-model="stockOutForm.itemName">
                <option>Select inventory item</option>
                <option v-for="i in inventory" :key="i.id">{{ i.name }} ({{ i.availableQty }} available)</option>
              </select>
            </div>
            <div class="field"><label class="field__label">Quantity Out <span class="req">*</span></label><input class="field__input" v-model="stockOutForm.quantity" type="number" min="1"/></div>
            <div class="field"><label class="field__label">Reason</label><select class="field__input" v-model="stockOutForm.reason"><option value="">Select reason</option><option>Issued to Department</option><option>Damaged / Disposed</option><option>Transfer</option><option>Write-Off</option><option>Other</option></select></div>
            <div class="field"><label class="field__label">Department</label>
              <select class="field__input" v-model="stockOutForm.department">
                <option value="">Select department</option>
                <option v-for="d in departments" :key="d.id">{{ d.name }}</option>
              </select>
            </div>
            <div class="field"><label class="field__label">Date</label><input class="field__input" v-model="stockOutForm.date" type="date"/></div>
            <div class="field"><label class="field__label">Notes</label><textarea class="field__input field__textarea" v-model="stockOutForm.notes" placeholder="Additional notes..." rows="2"></textarea></div>
            <button class="btn btn--primary btn--full" @click="submitStockOut">Record Stock Out</button>
          </div>
        </div>
      </div>
    </div>

    <!-- ── BORROWING ── -->
    <div v-else-if="adminView==='borrowing'">
      <div class="page-header"><div><h1 class="page-title">Borrowing</h1><p class="page-sub">{{ borrowings.filter(b=>b.status==='Borrowed').length }} active borrowing(s)</p></div></div>
      <div class="scard mb-20">
        <div class="filter-bar">
          <div class="tab-group"><button class="tab" :class="{'tab--on':borrowTab==='Active'}" @click="borrowTab='Active'">Active</button><button class="tab" :class="{'tab--on':borrowTab==='Returned'}" @click="borrowTab='Returned'">Returned</button><button class="tab" :class="{'tab--on':borrowTab==='All'}" @click="borrowTab='All'">All</button></div>
          <div class="search-wrap"><span class="search-icon">🔍</span><input class="search-input" v-model="borrowSearch" placeholder="Search borrowing records..."/></div>
        </div>
      </div>
      <div class="scard">
        <table class="tbl">
          <thead><tr><th>Borrower</th><th>Item</th><th>Borrow Date</th><th>Due Date</th><th>Duration</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
            <tr v-if="filteredBorrowings.length===0"><td colspan="7"><div class="empty"><div class="empty__icon">🤲</div><div class="empty__title">No borrowing records</div><p class="empty__desc">Equipment borrowing records will appear here once users begin borrowing items.</p></div></td></tr>
            <tr v-for="b in filteredBorrowings" :key="b.id">
              <td>{{ b.borrowerName }}</td><td>{{ b.itemName }}</td><td>{{ fmtDate(b.borrowDate) }}</td><td>{{ fmtDate(b.dueDate) }}</td><td>{{ b.duration||'—' }}</td>
              <td><span class="badge-pill" :class="statusClass(b.status)">{{ b.status }}</span></td>
              <td><button v-if="b.status==='Borrowed'" class="btn btn--success btn--sm" @click="processReturn(b)">Process Return</button><span v-else class="text-sub">Returned {{ fmtDate(b.returnDate) }}</span></td>
            </tr>
          </tbody>
        </table>
        <div class="tbl-foot"><span class="tbl-count">{{ filteredBorrowings.length }} record(s)</span></div>
      </div>
    </div>

    <!-- ── RETURNS ── -->
    <div v-else-if="adminView==='returns'">
      <div class="page-header"><div><h1 class="page-title">Returns</h1><p class="page-sub">{{ pendingReturns.length }} item(s) pending return</p></div></div>
      <div class="scard mb-20"><div class="filter-bar"><div class="tab-group"><button class="tab" :class="{'tab--on':returnsTab==='Pending Return'}" @click="returnsTab='Pending Return'">Pending Return</button><button class="tab" :class="{'tab--on':returnsTab==='Return History'}" @click="returnsTab='Return History'">Return History</button></div></div></div>
      <div class="scard">
        <table class="tbl">
          <thead v-if="returnsTab==='Pending Return'"><tr><th>Borrower</th><th>Item</th><th>Borrow Date</th><th>Due Date</th><th>Action</th></tr></thead>
          <thead v-else><tr><th>Borrower</th><th>Item</th><th>Borrow Date</th><th>Return Date</th><th>Condition</th></tr></thead>
          <tbody>
            <template v-if="returnsTab==='Pending Return'">
              <tr v-if="pendingReturns.length===0"><td colspan="5"><div class="empty"><div class="empty__icon">↩️</div><div class="empty__title">No pending returns</div><p class="empty__desc">All borrowed items have been returned.</p></div></td></tr>
              <tr v-for="b in pendingReturns" :key="b.id">
                <td>{{ b.borrowerName }}</td><td>{{ b.itemName }}</td><td>{{ fmtDate(b.borrowDate) }}</td><td>{{ fmtDate(b.dueDate) }}</td>
                <td><button class="btn btn--success btn--sm" @click="processReturn(b)">Mark as Returned</button></td>
              </tr>
            </template>
            <template v-else>
              <tr v-if="completedReturns.length===0"><td colspan="5"><div class="empty"><div class="empty__icon">📋</div><div class="empty__title">No return history</div><p class="empty__desc">Completed returns will appear here.</p></div></td></tr>
              <tr v-for="r in completedReturns" :key="r.id">
                <td>{{ r.borrowerName }}</td><td>{{ r.itemName }}</td><td>{{ fmtDate(r.borrowDate) }}</td><td>{{ fmtDate(r.returnDate) }}</td><td>{{ r.condition||'Good' }}</td>
              </tr>
            </template>
          </tbody>
        </table>
        <div class="tbl-foot"><span class="tbl-count">{{ returnsTab==='Pending Return' ? pendingReturns.length : completedReturns.length }} record(s)</span></div>
      </div>
    </div>

    <!-- ── REQUESTS ── -->
    <div v-else-if="adminView==='requests'">
      <div class="page-header"><div><h1 class="page-title">Request Management</h1><p class="page-sub">{{ requests.filter(r=>r.status==='Pending').length }} pending request(s)</p></div></div>
      <div class="scard mb-20">
        <div class="filter-bar">
          <div class="tab-group">
            <button class="tab" :class="{'tab--on':reqTab==='Pending'}" @click="reqTab='Pending'">Pending <span v-if="requests.filter(r=>r.status==='Pending').length>0" class="tab-count">{{ requests.filter(r=>r.status==='Pending').length }}</span></button>
            <button class="tab" :class="{'tab--on':reqTab==='Approved'}" @click="reqTab='Approved'">Approved</button>
            <button class="tab" :class="{'tab--on':reqTab==='Rejected'}" @click="reqTab='Rejected'">Rejected</button>
            <button class="tab" :class="{'tab--on':reqTab==='Completed'}" @click="reqTab='Completed'">Completed</button>
            <button class="tab" :class="{'tab--on':reqTab==='All'}" @click="reqTab='All'">All</button>
          </div>
          <div class="search-wrap"><span class="search-icon">🔍</span><input class="search-input" v-model="reqSearch" placeholder="Search requests..."/></div>
        </div>
      </div>
      <div class="scard">
        <table class="tbl">
          <thead><tr><th>Request ID</th><th>Requester</th><th>Item</th><th>Type</th><th>Submitted</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
            <tr v-if="filteredRequests.length===0"><td colspan="7"><div class="empty"><div class="empty__icon">📋</div><div class="empty__title">No {{ reqTab!=='All'?reqTab.toLowerCase():'' }} requests</div><p class="empty__desc">Requests will appear here once users submit them.</p></div></td></tr>
            <tr v-for="r in filteredRequests" :key="r.id">
              <td><span class="tbl-link" @click="viewRequest(r)">{{ r.requestId }}</span></td>
              <td>{{ r.requesterName }}</td><td>{{ r.itemName }}</td><td>{{ r.type }}</td><td>{{ r.submittedAt }}</td>
              <td><span class="badge-pill" :class="statusClass(r.status)">{{ r.status }}</span></td>
              <td>
                <div class="action-row">
                  <button class="btn-icon" title="View" @click="viewRequest(r)">👁️</button>
                  <button v-if="r.status==='Pending'" class="btn btn--success btn--sm" @click="openApprove(r)">Approve</button>
                  <button v-if="r.status==='Pending'" class="btn btn--danger btn--sm" @click="openReject(r)">Reject</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <div class="tbl-foot"><span class="tbl-count">{{ filteredRequests.length }} record(s)</span></div>
      </div>
    </div>

    <!-- ── REQUEST DETAIL ── -->
    <div v-else-if="adminView==='request-detail'&&selectedRequest">
      <div class="page-header">
        <div class="page-header__back">
          <button class="back-btn" @click="goAdmin('requests')">←</button>
          <div><h1 class="page-title">Request {{ selectedRequest.requestId }}</h1><p class="page-sub">{{ selectedRequest.type }} — {{ selectedRequest.itemName }}</p></div>
        </div>
        <div class="flex-row gap-10" v-if="selectedRequest.status==='Pending'">
          <button class="btn btn--danger btn--sm" @click="openReject(selectedRequest)">Reject</button>
          <button class="btn btn--success btn--sm" @click="openApprove(selectedRequest)">✓ Approve</button>
        </div>
      </div>
      <div class="two-col-sidebar">
        <div class="form-stack">
          <div class="scard p-24">
            <div class="scard__title mb-16">Request Information</div>
            <div class="detail-grid">
              <div class="detail-field"><div class="detail-label">Request ID</div><div class="detail-val">{{ selectedRequest.requestId }}</div></div>
              <div class="detail-field"><div class="detail-label">Type</div><div class="detail-val">{{ selectedRequest.type }}</div></div>
              <div class="detail-field"><div class="detail-label">Status</div><div class="detail-val"><span class="badge-pill" :class="statusClass(selectedRequest.status)">{{ selectedRequest.status }}</span></div></div>
              <div class="detail-field"><div class="detail-label">Submitted On</div><div class="detail-val">{{ selectedRequest.submittedAt }}</div></div>
              <div class="detail-field"><div class="detail-label">Item</div><div class="detail-val">{{ selectedRequest.itemName }}</div></div>
              <div class="detail-field"><div class="detail-label">Duration</div><div class="detail-val">{{ selectedRequest.duration||'—' }}</div></div>
              <div class="detail-field" style="grid-column:1/-1"><div class="detail-label">Purpose</div><div class="detail-val">{{ selectedRequest.purpose||'—' }}</div></div>
              <div class="detail-field" style="grid-column:1/-1" v-if="selectedRequest.notes"><div class="detail-label">Notes</div><div class="detail-val">{{ selectedRequest.notes }}</div></div>
              <div class="detail-field" v-if="selectedRequest.rejectReason" style="grid-column:1/-1"><div class="detail-label">Rejection Reason</div><div class="detail-val" style="color:#E74C3C">{{ selectedRequest.rejectReason }}</div></div>
              <div class="detail-field" v-if="selectedRequest.adminNote" style="grid-column:1/-1"><div class="detail-label">Admin Note</div><div class="detail-val">{{ selectedRequest.adminNote }}</div></div>
            </div>
          </div>
          <div class="scard p-24">
            <div class="scard__title mb-12">Requester: {{ selectedRequest.requesterName }}</div>
            <div class="detail-grid">
              <div class="detail-field"><div class="detail-label">Name</div><div class="detail-val">{{ selectedRequest.requesterName }}</div></div>
              <div class="detail-field"><div class="detail-label">Submitted</div><div class="detail-val">{{ selectedRequest.submittedAt }}</div></div>
            </div>
          </div>
          <div class="scard p-24">
            <div class="scard__title mb-12">Admin Notes</div>
            <textarea class="field__input field__textarea" v-model="adminReqNote" placeholder="Add private notes for this request..." rows="3"></textarea>
            <button class="btn btn--ghost btn--sm mt-12" @click="saveAdminNote">Save Note</button>
          </div>
        </div>
        <div class="form-stack">
          <div class="scard p-24">
            <div class="scard__title mb-12">Status</div>
            <div style="text-align:center;padding:8px 0">
              <div class="mb-12"><span class="badge-pill" :class="statusClass(selectedRequest.status)">{{ selectedRequest.status }}</span></div>
              <template v-if="selectedRequest.status==='Pending'">
                <button class="btn btn--success btn--full mb-10" @click="openApprove(selectedRequest)">✓ Approve Request</button>
                <button class="btn btn--danger btn--full" @click="openReject(selectedRequest)">✕ Reject Request</button>
              </template>
              <p v-else class="text-sub" style="font-size:12px">Decision made on {{ selectedRequest.decidedAt||'—' }}</p>
            </div>
          </div>
          <div class="scard p-24">
            <div class="scard__title mb-12">Timeline</div>
            <div class="timeline">
              <div class="tl-item tl-item--done"><div class="tl-dot tl-dot--done">✓</div><div><div class="tl-title">Submitted</div><div class="tl-sub">{{ selectedRequest.submittedAt }}</div></div></div>
              <div class="tl-item" :class="{'tl-item--done':selectedRequest.status!=='Pending'}"><div class="tl-dot" :class="{'tl-dot--done':selectedRequest.status!=='Pending'}">{{ selectedRequest.status!=='Pending'?'✓':'·' }}</div><div><div class="tl-title">Decision</div><div class="tl-sub">{{ selectedRequest.status==='Pending'?'Awaiting review':selectedRequest.status }}</div></div></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── USERS ── -->
    <div v-else-if="adminView==='users'">
      <div class="page-header">
        <div><h1 class="page-title">User Management</h1><p class="page-sub">{{ users.length }} user(s) registered</p></div>
        <button class="btn btn--primary btn--sm" @click="openAddUser">+ Add User</button>
      </div>
      <div class="scard mb-20">
        <div class="filter-bar">
          <div class="search-wrap"><span class="search-icon">🔍</span><input class="search-input" v-model="usersSearch" placeholder="Search users..."/></div>
          <select class="sel" v-model="usersRoleFilter"><option>All Roles</option><option>Student</option><option>Faculty</option><option>Staff</option><option>Administrator</option></select>
          <select class="sel" v-model="usersStatusFilter"><option>All Status</option><option>Active</option><option>Inactive</option></select>
        </div>
      </div>
      <div class="scard">
        <table class="tbl">
          <thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Department</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
            <tr v-if="filteredUsers.length===0"><td colspan="6"><div class="empty"><div class="empty__icon">👥</div><div class="empty__title">No user accounts found</div><p class="empty__desc">Add users to the system to manage their access.</p><button class="btn btn--primary btn--sm mt-12" @click="openAddUser">+ Add First User</button></div></td></tr>
            <tr v-for="u in filteredUsers" :key="u.id">
              <td><span class="tbl-link" @click="viewUser(u)">{{ u.name }}</span></td>
              <td>{{ u.username }}</td><td>{{ u.role }}</td><td>{{ u.department||'—' }}</td>
              <td><span class="badge-pill" :class="statusClass(u.status)">{{ u.status }}</span></td>
              <td>
                <div class="action-row">
                  <button class="btn-icon" @click="viewUser(u)">👁️</button>
                  <button class="btn-icon" @click="openEditUser(u)">✏️</button>
                  <button class="btn-icon btn-icon--red" @click="openDeactivate(u)">{{ u.status==='Active'?'🔒':'🔓' }}</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <div class="tbl-foot"><span class="tbl-count">{{ filteredUsers.length }} user(s)</span></div>
      </div>
    </div>

    <!-- ── USER DETAIL ── -->
    <div v-else-if="adminView==='user-detail'&&selectedUser">
      <div class="page-header">
        <div class="page-header__back"><button class="back-btn" @click="goAdmin('users')">←</button><div><h1 class="page-title">{{ selectedUser.name }}</h1><p class="page-sub">User Details</p></div></div>
        <div class="flex-row gap-10">
          <button class="btn btn--secondary btn--sm" @click="openDeactivate(selectedUser)">{{ selectedUser.status==='Active'?'Deactivate':'Activate' }}</button>
          <button class="btn btn--primary btn--sm" @click="openEditUser(selectedUser)">✏️ Edit</button>
        </div>
      </div>
      <div class="two-col-sidebar">
        <div class="form-stack">
          <div class="scard p-24">
            <div class="scard__title mb-16">Account Information</div>
            <div class="detail-grid">
              <div class="detail-field"><div class="detail-label">Full Name</div><div class="detail-val">{{ selectedUser.name }}</div></div>
              <div class="detail-field"><div class="detail-label">Username</div><div class="detail-val">{{ selectedUser.username }}</div></div>
              <div class="detail-field"><div class="detail-label">Role</div><div class="detail-val">{{ selectedUser.role }}</div></div>
              <div class="detail-field"><div class="detail-label">Department</div><div class="detail-val">{{ selectedUser.department||'—' }}</div></div>
              <div class="detail-field"><div class="detail-label">Email</div><div class="detail-val">{{ selectedUser.email||'—' }}</div></div>
              <div class="detail-field"><div class="detail-label">ID Number</div><div class="detail-val">{{ selectedUser.idNumber||'—' }}</div></div>
              <div class="detail-field"><div class="detail-label">Account Created</div><div class="detail-val">{{ fmtDate(selectedUser.createdAt) }}</div></div>
              <div class="detail-field"><div class="detail-label">Status</div><div class="detail-val"><span class="badge-pill" :class="statusClass(selectedUser.status)">{{ selectedUser.status }}</span></div></div>
            </div>
          </div>
          <div class="scard">
            <div class="scard__hd"><span class="scard__title">Request History</span></div>
            <div v-if="userRequestHistory.length===0" class="empty"><div class="empty__icon">📋</div><div class="empty__title">No request history</div><p class="empty__desc">This user has not submitted any requests yet.</p></div>
            <table v-else class="tbl">
              <thead><tr><th>ID</th><th>Item</th><th>Type</th><th>Status</th></tr></thead>
              <tbody><tr v-for="r in userRequestHistory" :key="r.id"><td>{{ r.requestId }}</td><td>{{ r.itemName }}</td><td>{{ r.type }}</td><td><span class="badge-pill" :class="statusClass(r.status)">{{ r.status }}</span></td></tr></tbody>
            </table>
          </div>
        </div>
        <div class="form-stack">
          <div class="scard p-24" style="text-align:center">
            <div style="width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,#EEF7FF,#D6E6F8);border:2px solid #D6E6F8;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;font-size:22px">👤</div>
            <div style="font-weight:700;font-size:15px;color:#032B5B;margin-bottom:6px">{{ selectedUser.name }}</div>
            <span class="badge-pill" :class="statusClass(selectedUser.status)">{{ selectedUser.status }}</span>
          </div>
          <div class="scard p-24">
            <div class="scard__title mb-12">Activity Summary</div>
            <div class="summary-rows">
              <div class="summary-row"><span class="summary-row__label">Total Requests</span><span class="summary-row__val">{{ userRequestCount }}</span></div>
              <div class="summary-row"><span class="summary-row__label">Approved</span><span class="summary-row__val" style="color:#3CB371">{{ userApprovedCount }}</span></div>
              <div class="summary-row"><span class="summary-row__label">Pending</span><span class="summary-row__val" style="color:#F59E0B">{{ userPendingCount }}</span></div>
              <div class="summary-row"><span class="summary-row__label">Active Loans</span><span class="summary-row__val" style="color:#1678D8">{{ userLoanCount }}</span></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── SUPPLIERS ── -->
    <div v-else-if="adminView==='suppliers'">
      <div class="page-header"><div><h1 class="page-title">Suppliers</h1><p class="page-sub">{{ suppliers.length }} supplier(s)</p></div><button class="btn btn--primary btn--sm" @click="openAddSupplier">+ Add Supplier</button></div>
      <div class="scard">
        <table class="tbl">
          <thead><tr><th>Supplier Name</th><th>Contact Person</th><th>Email</th><th>Phone</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
            <tr v-if="suppliers.length===0"><td colspan="6"><div class="empty"><div class="empty__icon">🚚</div><div class="empty__title">No suppliers added yet</div><p class="empty__desc">Add suppliers to track procurement sources.</p><button class="btn btn--primary btn--sm mt-12" @click="openAddSupplier">+ Add First Supplier</button></div></td></tr>
            <tr v-for="s in suppliers" :key="s.id">
              <td>{{ s.name }}</td><td>{{ s.contactPerson||'—' }}</td><td>{{ s.email||'—' }}</td><td>{{ s.phone||'—' }}</td>
              <td><span class="badge-pill" :class="statusClass(s.status)">{{ s.status }}</span></td>
              <td><div class="action-row"><button class="btn-icon" @click="openEditSupplier(s)">✏️</button><button class="btn-icon btn-icon--red" @click="confirmDeleteSupplier(s)">🗑️</button></div></td>
            </tr>
          </tbody>
        </table>
        <div class="tbl-foot"><span class="tbl-count">{{ suppliers.length }} record(s)</span></div>
      </div>
    </div>

    <!-- ── DEPARTMENTS ── -->
    <div v-else-if="adminView==='departments'">
      <div class="page-header"><div><h1 class="page-title">Departments</h1><p class="page-sub">{{ departments.length }} department(s)</p></div><button class="btn btn--primary btn--sm" @click="openAddDept">+ Add Department</button></div>
      <div class="two-col-sidebar">
        <div class="scard">
          <table class="tbl">
            <thead><tr><th>Department Name</th><th>Code</th><th>Head / Contact</th><th>Actions</th></tr></thead>
            <tbody>
              <tr v-if="departments.length===0"><td colspan="4"><div class="empty"><div class="empty__icon">🏢</div><div class="empty__title">No departments added</div><p class="empty__desc">Add departments to organize your institution's ICT resources.</p><button class="btn btn--primary btn--sm mt-12" @click="openAddDept">+ Add First Department</button></div></td></tr>
              <tr v-for="d in departments" :key="d.id">
                <td>{{ d.name }}</td><td>{{ d.code||'—' }}</td><td>{{ d.head||'—' }}</td>
                <td><div class="action-row"><button class="btn-icon" @click="openEditDept(d)">✏️</button><button class="btn-icon btn-icon--red" @click="deleteDept(d)">🗑️</button></div></td>
              </tr>
            </tbody>
          </table>
          <div class="tbl-foot"><span class="tbl-count">{{ departments.length }} record(s)</span></div>
        </div>
        <div class="scard p-24">
          <div class="scard__title mb-12">Department Guide</div>
          <p class="help-text">Departments help organize ICT resources. You can:</p>
          <ul class="help-list"><li>Assign inventory to departments</li><li>Filter reports by department</li><li>Track equipment usage per department</li><li>Set department-level access rules</li></ul>
        </div>
      </div>
    </div>

    <!-- ── CATEGORIES ── -->
    <div v-else-if="adminView==='categories'">
      <div class="page-header"><div><h1 class="page-title">Category Management</h1><p class="page-sub">{{ categories.length }} custom category(ies)</p></div><button class="btn btn--primary btn--sm" @click="openAddCat">+ Add Category</button></div>
      <div class="two-col-sidebar">
        <div class="scard">
          <table class="tbl">
            <thead><tr><th>Category Name</th><th>Description</th><th>Item Count</th><th>Actions</th></tr></thead>
            <tbody>
              <tr v-if="categories.length===0"><td colspan="4"><div class="empty"><div class="empty__icon">🗂️</div><div class="empty__title">No custom categories yet</div><p class="empty__desc">Add categories to organize your ICT inventory. Default categories are available in item forms.</p><button class="btn btn--primary btn--sm mt-12" @click="openAddCat">+ Add First Category</button></div></td></tr>
              <tr v-for="c in categories" :key="c.id">
                <td>{{ c.name }}</td><td>{{ c.description||'—' }}</td>
                <td>{{ inventory.filter(i=>i.category===c.name).length }}</td>
                <td><div class="action-row"><button class="btn-icon" @click="openEditCat(c)">✏️</button><button class="btn-icon btn-icon--red" @click="deleteCat(c)">🗑️</button></div></td>
              </tr>
            </tbody>
          </table>
          <div class="tbl-foot"><span class="tbl-count">{{ categories.length }} record(s)</span></div>
        </div>
        <div class="scard p-24">
          <div class="scard__title mb-12">Default Categories</div>
          <ul class="help-list"><li>Computers &amp; Laptops</li><li>Networking Equipment</li><li>Peripherals</li><li>Audio/Visual Equipment</li><li>Storage Devices</li><li>Mobile Devices</li><li>Cables &amp; Accessories</li></ul>
          <p class="help-text mt-12">These are built-in and always available in item forms. Add custom ones above.</p>
        </div>
      </div>
    </div>

    <!-- ── REPORTS ── -->
    <div v-else-if="adminView==='reports'">
      <div class="page-header"><div><h1 class="page-title">Reports</h1><p class="page-sub">Generate and export inventory management reports</p></div><button v-if="reportGenerated" class="btn btn--secondary btn--sm">⬇ Export CSV</button></div>
      <div class="two-col-sidebar two-col-sidebar--reverse">
        <div class="scard p-24">
          <div class="scard__title mb-16">Report Type</div>
          <div v-for="t in reportTypes" :key="t" class="report-type-item" :class="{'report-type-item--on':reportType===t}" @click="selectReportType(t)">
            <div class="rt-label">{{ t }}</div>
          </div>
        </div>
        <div class="form-stack">
          <div class="scard p-24">
            <div class="scard__title mb-16">{{ reportType }}</div>
            <div class="three-col-sm mb-16">
              <div class="field"><label class="field__label">Date From</label><input class="field__input" v-model="reportFrom" type="date"/></div>
              <div class="field"><label class="field__label">Date To</label><input class="field__input" v-model="reportTo" type="date"/></div>
              <div class="field"><label class="field__label">Category</label><select class="field__input" v-model="reportCat"><option>All Categories</option><option v-for="c in categories" :key="c.id">{{ c.name }}</option></select></div>
            </div>
            <button class="btn btn--primary btn--sm" @click="generateReport">📊 Generate Report</button>
          </div>
          <div class="scard">
            <div v-if="!reportGenerated" class="empty"><div class="empty__icon">📊</div><div class="empty__title">Configure and generate a report</div><p class="empty__desc">Select filters above, then click Generate Report.</p></div>
            <div v-else-if="reportData.length===0" class="empty"><div class="empty__icon">📊</div><div class="empty__title">No data for this report</div><p class="empty__desc">No records match the selected filters. Add inventory and process requests to see data.</p></div>
            <div v-else>
              <div style="padding:14px 20px;border-bottom:1px solid #D6E6F8;font-size:13px;font-weight:700;color:#17324D">{{ reportType }} — {{ reportData.length }} record(s)</div>
              <table class="tbl">
                <thead v-if="reportType==='Inventory Report'"><tr><th>Item</th><th>Category</th><th>Total</th><th>Available</th><th>Status</th></tr></thead>
                <thead v-else-if="reportType==='Borrowing Report'"><tr><th>Borrower</th><th>Item</th><th>Borrow Date</th><th>Due Date</th><th>Status</th></tr></thead>
                <thead v-else-if="reportType==='Request Report'"><tr><th>ID</th><th>Requester</th><th>Item</th><th>Type</th><th>Status</th></tr></thead>
                <thead v-else-if="reportType==='Stock Movement'"><tr><th>Direction</th><th>Item</th><th>Qty</th><th>Date</th></tr></thead>
                <thead v-else><tr><th>Timestamp</th><th>User</th><th>Event</th><th>Description</th></tr></thead>
                <tbody>
                  <template v-if="reportType==='Inventory Report'">
                    <tr v-for="i in reportData" :key="i.id"><td>{{ i.name }}</td><td>{{ i.category||'—' }}</td><td>{{ i.totalQty }}</td><td>{{ i.availableQty }}</td><td><span class="badge-pill" :class="statusClass(i.status)">{{ i.status }}</span></td></tr>
                  </template>
                  <template v-else-if="reportType==='Borrowing Report'">
                    <tr v-for="b in reportData" :key="b.id"><td>{{ b.borrowerName }}</td><td>{{ b.itemName }}</td><td>{{ fmtDate(b.borrowDate) }}</td><td>{{ fmtDate(b.dueDate) }}</td><td><span class="badge-pill" :class="statusClass(b.status)">{{ b.status }}</span></td></tr>
                  </template>
                  <template v-else-if="reportType==='Request Report'">
                    <tr v-for="r in reportData" :key="r.id"><td>{{ r.requestId }}</td><td>{{ r.requesterName }}</td><td>{{ r.itemName }}</td><td>{{ r.type }}</td><td><span class="badge-pill" :class="statusClass(r.status)">{{ r.status }}</span></td></tr>
                  </template>
                  <template v-else-if="reportType==='Stock Movement'">
                    <tr v-for="r in reportData" :key="r.id"><td><span class="badge-pill" :class="r.direction==='IN'?'badge-ok':'badge-err'">{{ r.direction }}</span></td><td>{{ r.itemName }}</td><td>{{ r.qty }}</td><td>{{ fmtDate(r.date||r.dateReceived) }}</td></tr>
                  </template>
                  <template v-else>
                    <tr v-for="l in reportData" :key="l.id"><td style="font-size:11px">{{ l.timestamp }}</td><td>{{ l.user }}</td><td>{{ l.eventType }}</td><td>{{ l.description }}</td></tr>
                  </template>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── AUDIT LOGS ── -->
    <div v-else-if="adminView==='audit-logs'">
      <div class="page-header"><div><h1 class="page-title">Audit Logs</h1><p class="page-sub">{{ auditLogs.length }} total event(s) — read-only</p></div><button class="btn btn--secondary btn--sm">⬇ Export Logs</button></div>
      <div class="scard mb-20">
        <div class="filter-bar">
          <div class="search-wrap"><span class="search-icon">🔍</span><input class="search-input" v-model="auditSearch" placeholder="Search logs..."/></div>
          <select class="sel" v-model="auditFilter"><option>All Events</option><option>Login</option><option>Logout</option><option>Registration</option><option>Inventory</option><option>Stock In</option><option>Stock Out</option><option>Request</option><option>Returns</option><option>Users</option><option>Category</option><option>Department</option><option>Supplier</option><option>Settings</option><option>Profile</option><option>Reports</option></select>
        </div>
      </div>
      <div class="scard">
        <table class="tbl">
          <thead><tr><th>Timestamp</th><th>User</th><th>Event Type</th><th>Description</th><th>IP Address</th></tr></thead>
          <tbody>
            <tr v-if="filteredAuditLogs.length===0"><td colspan="5"><div class="empty"><div class="empty__icon">📝</div><div class="empty__title">No audit log entries</div><p class="empty__desc">System events and actions will be recorded here automatically as you use the platform.</p></div></td></tr>
            <tr v-for="l in filteredAuditLogs" :key="l.id">
              <td style="font-size:11px;white-space:nowrap">{{ l.timestamp }}</td>
              <td>{{ l.user }}</td>
              <td><span class="badge-pill badge-blue">{{ l.eventType }}</span></td>
              <td>{{ l.description }}</td>
              <td style="font-size:12px;color:#5D728A">{{ l.ip }}</td>
            </tr>
          </tbody>
        </table>
        <div class="tbl-foot"><span class="tbl-count">{{ filteredAuditLogs.length }} record(s)</span></div>
      </div>
    </div>

    <!-- ── NOTIFICATIONS (admin) ── -->
    <div v-else-if="adminView==='notifications'">
      <div class="page-header">
        <div><h1 class="page-title">Notifications</h1><p class="page-sub">{{ unreadAdminCount }} unread</p></div>
        <button class="btn btn--ghost btn--sm" @click="markAllAdminRead">Mark All as Read</button>
      </div>
      <div class="scard p-24">
        <div class="tab-group mb-20">
          <button class="tab" :class="{'tab--on':adminNotifTab==='All'}" @click="adminNotifTab='All'">All</button>
          <button class="tab" :class="{'tab--on':adminNotifTab==='Requests'}" @click="adminNotifTab='Requests'">Requests</button>
          <button class="tab" :class="{'tab--on':adminNotifTab==='Stock'}" @click="adminNotifTab='Stock'">Stock</button>
          <button class="tab" :class="{'tab--on':adminNotifTab==='System'}" @click="adminNotifTab='System'">System</button>
          <button class="tab" :class="{'tab--on':adminNotifTab==='Unread'}" @click="adminNotifTab='Unread'">Unread <span v-if="unreadAdminCount>0" class="tab-count">{{ unreadAdminCount }}</span></button>
        </div>
        <div v-if="filteredAdminNotifs.length===0" class="empty"><div class="empty__icon">🔔</div><div class="empty__title">No notifications</div><p class="empty__desc">Notifications will appear here when users submit requests and system events occur.</p></div>
        <div v-for="n in filteredAdminNotifs" :key="n.id" class="notif-item" :class="{'notif-item--unread':!n.read}" @click="markAdminNotifRead(n)">
          <div class="notif-dot" v-if="!n.read"></div>
          <div class="notif-body">
            <div class="notif-msg">{{ n.message }}</div>
            <div class="notif-time">{{ n.createdAt }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── SETTINGS ── -->
    <div v-else-if="adminView==='settings'">
      <div class="page-header"><div><h1 class="page-title">System Settings</h1><p class="page-sub">Configure TechVault platform settings</p></div><button class="btn btn--primary btn--sm" @click="saveSettings">✓ Save All Changes</button></div>
      <div v-if="settingsSaved" class="alert alert--ok mb-20">✓ Settings saved successfully.</div>
      <div class="settings-grid">
        <div class="scard p-24">
          <div class="scard__title mb-16">General Settings</div>
          <div class="form-stack">
            <div class="field"><label class="field__label">System Name</label><input class="field__input" v-model="settings.systemName" type="text"/></div>
            <div class="field"><label class="field__label">Institution Name</label><input class="field__input" v-model="settings.institution" type="text"/></div>
            <div class="field"><label class="field__label">Department Name</label><input class="field__input" v-model="settings.department" type="text"/></div>
            <div class="field"><label class="field__label">Support Email</label><input class="field__input" v-model="settings.email" type="email"/></div>
          </div>
        </div>
        <div class="scard p-24">
          <div class="scard__title mb-16">Inventory Settings</div>
          <div class="form-stack">
            <div class="toggle-row"><span class="toggle-label">Allow equipment borrow requests</span><div class="toggle" :class="{'toggle--on':settings.allowRequests}" @click="settings.allowRequests=!settings.allowRequests"><div class="toggle__knob" :class="{'toggle__knob--on':settings.allowRequests}"></div></div></div>
            <div class="toggle-row"><span class="toggle-label">Require admin approval for requests</span><div class="toggle" :class="{'toggle--on':settings.requireApproval}" @click="settings.requireApproval=!settings.requireApproval"><div class="toggle__knob" :class="{'toggle__knob--on':settings.requireApproval}"></div></div></div>
            <div class="toggle-row"><span class="toggle-label">Auto-archive inactive inventory items</span><div class="toggle" :class="{'toggle--on':settings.autoArchive}" @click="settings.autoArchive=!settings.autoArchive"><div class="toggle__knob" :class="{'toggle__knob--on':settings.autoArchive}"></div></div></div>
            <div class="field"><label class="field__label">Default Max Borrow Duration</label><select class="field__input" v-model="settings.maxBorrow"><option>1 day</option><option>3 days</option><option>1 week</option><option>2 weeks</option><option>1 month</option></select></div>
            <div class="field"><label class="field__label">Low Stock Threshold</label><select class="field__input" v-model="settings.lowStockThreshold"><option>1 unit</option><option>2 units</option><option>5 units</option><option>10 units</option></select></div>
          </div>
        </div>
        <div class="scard p-24">
          <div class="scard__title mb-16">Notification Settings</div>
          <div class="form-stack">
            <div class="toggle-row"><span class="toggle-label">Send email notifications</span><div class="toggle" :class="{'toggle--on':settings.emailNotif}" @click="settings.emailNotif=!settings.emailNotif"><div class="toggle__knob" :class="{'toggle__knob--on':settings.emailNotif}"></div></div></div>
            <div class="toggle-row"><span class="toggle-label">Notify admin on new requests</span><div class="toggle" :class="{'toggle--on':settings.notifyAdmin}" @click="settings.notifyAdmin=!settings.notifyAdmin"><div class="toggle__knob" :class="{'toggle__knob--on':settings.notifyAdmin}"></div></div></div>
            <div class="toggle-row"><span class="toggle-label">Notify users on request status change</span><div class="toggle" :class="{'toggle--on':settings.notifyUsers}" @click="settings.notifyUsers=!settings.notifyUsers"><div class="toggle__knob" :class="{'toggle__knob--on':settings.notifyUsers}"></div></div></div>
            <div class="toggle-row"><span class="toggle-label">Send return reminders</span><div class="toggle" :class="{'toggle--on':settings.returnReminders}" @click="settings.returnReminders=!settings.returnReminders"><div class="toggle__knob" :class="{'toggle__knob--on':settings.returnReminders}"></div></div></div>
            <div class="toggle-row"><span class="toggle-label">Low stock alerts</span><div class="toggle" :class="{'toggle--on':settings.lowStockAlerts}" @click="settings.lowStockAlerts=!settings.lowStockAlerts"><div class="toggle__knob" :class="{'toggle__knob--on':settings.lowStockAlerts}"></div></div></div>
          </div>
        </div>
        <div class="scard p-24">
          <div class="scard__title mb-16">Security Settings</div>
          <div class="form-stack">
            <div class="toggle-row"><span class="toggle-label">Require two-factor authentication (admin)</span><div class="toggle" :class="{'toggle--on':settings.twoFactor}" @click="settings.twoFactor=!settings.twoFactor"><div class="toggle__knob" :class="{'toggle__knob--on':settings.twoFactor}"></div></div></div>
            <div class="toggle-row"><span class="toggle-label">Allow user self-registration</span><div class="toggle" :class="{'toggle--on':settings.selfReg}" @click="settings.selfReg=!settings.selfReg"><div class="toggle__knob" :class="{'toggle__knob--on':settings.selfReg}"></div></div></div>
            <div class="toggle-row"><span class="toggle-label">Maintenance mode (disables user access)</span><div class="toggle" :class="{'toggle--on':settings.maintenance}" @click="settings.maintenance=!settings.maintenance"><div class="toggle__knob" :class="{'toggle__knob--on':settings.maintenance}"></div></div></div>
            <div class="field"><label class="field__label">Session Timeout</label><select class="field__input" v-model="settings.sessionTimeout"><option>30 minutes</option><option>1 hour</option><option>4 hours</option><option>8 hours</option><option>24 hours</option></select></div>
            <div class="field"><label class="field__label">Password Policy</label><select class="field__input" v-model="settings.passwordPolicy"><option>Standard (8+ characters)</option><option>Strong (12+ with special chars)</option></select></div>
          </div>
        </div>
        <div class="scard p-24">
          <div class="scard__title mb-16">Request Settings</div>
          <div class="form-stack">
            <div class="field"><label class="field__label">Request Numbering Format</label><select class="field__input" v-model="settings.requestFormat"><option>REQ-YYYY-####</option><option>YYYY-MM-####</option><option>Sequential (####)</option></select></div>
            <div class="field"><label class="field__label">Borrowing Terms &amp; Conditions</label><textarea class="field__input field__textarea" v-model="settings.terms" rows="4" placeholder="Enter the terms users must agree to when submitting a borrow request..."></textarea></div>
          </div>
        </div>
        <div class="scard p-24">
          <div class="scard__title mb-16">Danger Zone</div>
          <div class="form-stack">
            <div class="danger-box"><div class="danger-box__title">Reset All Settings</div><div class="danger-box__desc">Revert all system settings to default values.</div><button class="btn btn--danger btn--sm" @click="showResetModal=true">Reset Settings</button></div>
            <div class="danger-box"><div class="danger-box__title">Clear All Inventory Records</div><div class="danger-box__desc">Permanently delete all inventory records. This is irreversible.</div><button class="btn btn--danger btn--sm" @click="showClearModal=true">Clear Records</button></div>
          </div>
        </div>
      </div>
    </div>

  </main>
</div>

<!-- ╔══════════════════════════════════════════════════════════════╗
     ║  USER PORTAL                                                ║
     ╚══════════════════════════════════════════════════════════════╝ -->
<div v-else-if="activeView==='user'" class="portal portal--user">
  <aside class="sidebar sidebar--user">
    <div class="sidebar__brand">
      <div class="logo"><div class="logo__icon">T</div><div><div class="logo__name logo__name--dark">TechVault</div><div class="logo__sub">ICT Inventory</div></div></div>
    </div>
    <nav class="sidebar__nav">
      <span class="nav-label nav-label--light">Navigation</span>
      <button class="nav-link nav-link--user" :class="{'nav-link--user-on':userView==='dashboard'}" @click="goUser('dashboard')">🏠 Dashboard</button>
      <button class="nav-link nav-link--user" :class="{'nav-link--user-on':userView.startsWith('inventory')}" @click="goUser('inventory')">📦 Inventory</button>
      <button class="nav-link nav-link--user" :class="{'nav-link--user-on':userView==='request-item'}" @click="goUser('request-item')">📋 Request Item</button>
      <button class="nav-link nav-link--user" :class="{'nav-link--user-on':userView==='borrow-equipment'}" @click="goUser('borrow-equipment')">🤲 Borrow Equipment</button>
      <button class="nav-link nav-link--user" :class="{'nav-link--user-on':userView.startsWith('my-request')}" @click="goUser('my-requests')">📄 My Requests <span v-if="userStats.pending>0" class="nav-badge nav-badge--user">{{ userStats.pending }}</span></button>
      <button class="nav-link nav-link--user" :class="{'nav-link--user-on':userView==='my-borrowed'}" @click="goUser('my-borrowed')">📦 My Borrowed Items</button>
      <button class="nav-link nav-link--user" :class="{'nav-link--user-on':userView==='returns'}" @click="goUser('returns')">↩️ Returns</button>
      <button class="nav-link nav-link--user" :class="{'nav-link--user-on':userView==='notifications'}" @click="goUser('notifications')">🔔 Notifications <span v-if="unreadUserCount>0" class="nav-badge nav-badge--user">{{ unreadUserCount }}</span></button>
      <button class="nav-link nav-link--user" :class="{'nav-link--user-on':userView==='profile'}" @click="goUser('profile')">👤 Profile</button>
    </nav>
    <div class="sidebar__footer-info"><div class="footer-info__inst">Liceo de Cagayan University</div><div class="footer-info__dept">IT Department</div></div>
    <button class="sidebar__logout sidebar__logout--user" @click="logout">↪ Logout</button>
  </aside>

  <main class="portal__main portal__main--user">

    <!-- ── USER DASHBOARD ── -->
    <div v-if="userView==='dashboard'">
      <div class="page-header"><div><h1 class="page-title">Dashboard</h1><p class="page-sub">Welcome, {{ currentUser?.name }} 👋</p></div></div>
      <div class="stat-grid stat-grid--4">
        <div class="stat-card stat-card--green"><div class="stat-val">{{ userStats.available }}</div><div class="stat-label">Available Items</div><div class="stat-sub">Items ready to borrow</div></div>
        <div class="stat-card stat-card--warn"><div class="stat-val">{{ userStats.pending }}</div><div class="stat-label">My Pending Requests</div><div class="stat-sub">Awaiting approval</div></div>
        <div class="stat-card stat-card--blue"><div class="stat-val">{{ userStats.approved }}</div><div class="stat-label">My Approved Requests</div><div class="stat-sub">Ready for pickup</div></div>
        <div class="stat-card stat-card--royal"><div class="stat-val">{{ userStats.borrowed }}</div><div class="stat-label">My Borrowed Equipment</div><div class="stat-sub">Currently checked out</div></div>
      </div>
      <div class="scard mb-20">
        <div class="scard__hd"><span class="scard__title">Quick Actions</span></div>
        <div class="quick-actions">
          <div class="qa-item" @click="goUser('inventory')"><div class="qa-icon">📦</div><div class="qa-label">Browse Inventory</div><div class="qa-sub">View all available ICT assets</div></div>
          <div class="qa-item" @click="goUser('request-item')"><div class="qa-icon">📋</div><div class="qa-label">Request an Item</div><div class="qa-sub">Submit a request for equipment</div></div>
          <div class="qa-item" @click="goUser('borrow-equipment')"><div class="qa-icon">🤲</div><div class="qa-label">Borrow Equipment</div><div class="qa-sub">Check out equipment immediately</div></div>
          <div class="qa-item" @click="goUser('my-borrowed')"><div class="qa-icon">📦</div><div class="qa-label">My Borrowed Items</div><div class="qa-sub">Track your borrowed items</div></div>
          <div class="qa-item" @click="goUser('returns')"><div class="qa-icon">↩️</div><div class="qa-label">Returns</div><div class="qa-sub">Submit a return for borrowed items</div></div>
          <div class="qa-item" @click="goUser('notifications')"><div class="qa-icon">🔔</div><div class="qa-label">Notifications</div><div class="qa-sub">View your latest alerts</div></div>
        </div>
      </div>
      <div class="two-col">
        <div class="scard">
          <div class="scard__hd"><span class="scard__title">My Recent Activity</span></div>
          <div v-if="recentUserActivity.length===0" class="empty"><div class="empty__icon">📋</div><div class="empty__title">No recent activity</div><p class="empty__desc">Your recent requests and borrowings will appear here.</p></div>
          <table v-else class="tbl">
            <thead><tr><th>Type</th><th>Item</th><th>Status</th></tr></thead>
            <tbody>
              <tr v-for="a in recentUserActivity" :key="a.id">
                <td>{{ a.type||'Borrowing' }}</td><td>{{ a.itemName }}</td>
                <td><span class="badge-pill" :class="statusClass(a.status)">{{ a.status }}</span></td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="scard">
          <div class="scard__hd"><span class="scard__title">System Status</span></div>
          <div class="status-list">
            <div class="status-item"><span class="status-name">Inventory System</span><span class="status-ok">● Operational</span></div>
            <div class="status-item"><span class="status-name">Request Processing</span><span class="status-ok">{{ adminStats.pending>0?'● Processing '+adminStats.pending+' request(s)':'● Operational' }}</span></div>
            <div class="status-item"><span class="status-name">Notification Service</span><span class="status-ok">● Operational</span></div>
          </div>
          <p class="help-text p-16">{{ settings.systemName }} — All services running normally.</p>
        </div>
      </div>
    </div>

    <!-- ── USER INVENTORY ── -->
    <div v-else-if="userView==='inventory'">
      <div class="page-header"><div><h1 class="page-title">Inventory</h1><p class="page-sub">Browse available ICT assets — {{ inventory.length }} item type(s)</p></div></div>
      <div class="scard mb-20">
        <div class="filter-bar">
          <div class="search-wrap"><span class="search-icon">🔍</span><input class="search-input" v-model="uInvSearch" placeholder="Search inventory..."/></div>
          <select class="sel" v-model="uInvCat"><option>All Categories</option><option v-for="c in categories" :key="c.id">{{ c.name }}</option><option>Computers & Laptops</option><option>Networking Equipment</option><option>Peripherals</option><option>Audio/Visual</option><option>Storage Devices</option></select>
          <select class="sel" v-model="uInvStatus"><option>All Status</option><option>Available</option><option>Borrowed</option><option>Under Maintenance</option></select>
        </div>
      </div>
      <div class="scard">
        <table class="tbl">
          <thead><tr><th>Item Name</th><th>Category</th><th>Location</th><th>Available Qty</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
            <tr v-if="filteredUserInv.length===0"><td colspan="6"><div class="empty"><div class="empty__icon">📦</div><div class="empty__title">No inventory records available</div><p class="empty__desc">Inventory information will appear here once records are added by the administrator.</p></div></td></tr>
            <tr v-for="item in filteredUserInv" :key="item.id">
              <td><span class="tbl-link" @click="viewUserItem(item)">{{ item.name }}</span></td>
              <td>{{ item.category||'—' }}</td><td>{{ item.location||'—' }}</td><td>{{ item.availableQty }}</td>
              <td><span class="badge-pill" :class="statusClass(item.status)">{{ item.status }}</span></td>
              <td>
                <div class="action-row">
                  <button class="btn-icon" @click="viewUserItem(item)">👁️</button>
                  <button v-if="item.availableQty>0" class="btn btn--primary btn--sm" @click="reqItemForm.itemName=item.name+' ('+item.availableQty+' available)'; goUser('request-item')">Request</button>
                  <button v-if="item.availableQty>0" class="btn btn--ghost btn--sm" @click="borrowItemForm.itemName=item.name+' ('+item.availableQty+' available)'; goUser('borrow-equipment')">Borrow</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <div class="tbl-foot"><span class="tbl-count">{{ filteredUserInv.length }} record(s)</span></div>
      </div>
    </div>

    <!-- ── INVENTORY DETAIL (user) ── -->
    <div v-else-if="userView==='inventory-detail'&&uSelectedItem">
      <div class="page-header">
        <div class="page-header__back"><button class="back-btn" @click="goUser('inventory')">←</button><div><h1 class="page-title">{{ uSelectedItem.name }}</h1><p class="page-sub">Item Details</p></div></div>
        <div class="flex-row gap-10">
          <button v-if="uSelectedItem.availableQty>0" class="btn btn--primary btn--sm" @click="reqItemForm.itemName=uSelectedItem.name+' ('+uSelectedItem.availableQty+' available)'; goUser('request-item')">Request Item</button>
          <button v-if="uSelectedItem.availableQty>0" class="btn btn--ghost btn--sm" @click="borrowItemForm.itemName=uSelectedItem.name+' ('+uSelectedItem.availableQty+' available)'; goUser('borrow-equipment')">Borrow</button>
        </div>
      </div>
      <div class="two-col-sidebar">
        <div class="scard p-24">
          <div class="scard__title mb-16">Equipment Information</div>
          <div class="detail-grid">
            <div class="detail-field"><div class="detail-label">Item Name</div><div class="detail-val">{{ uSelectedItem.name }}</div></div>
            <div class="detail-field"><div class="detail-label">Category</div><div class="detail-val">{{ uSelectedItem.category||'—' }}</div></div>
            <div class="detail-field"><div class="detail-label">Location</div><div class="detail-val">{{ uSelectedItem.location||'—' }}</div></div>
            <div class="detail-field"><div class="detail-label">Status</div><div class="detail-val"><span class="badge-pill" :class="statusClass(uSelectedItem.status)">{{ uSelectedItem.status }}</span></div></div>
          </div>
          <div v-if="uSelectedItem.description" class="mt-16"><div class="detail-label mb-6">Description</div><p style="font-size:13px;color:#5D728A;line-height:1.7">{{ uSelectedItem.description }}</p></div>
        </div>
        <div class="scard p-24">
          <div class="scard__title mb-12">Availability</div>
          <div class="summary-rows">
            <div class="summary-row"><span class="summary-row__label">Total Units</span><span class="summary-row__val">{{ uSelectedItem.totalQty }}</span></div>
            <div class="summary-row"><span class="summary-row__label">Available</span><span class="summary-row__val" style="color:#3CB371">{{ uSelectedItem.availableQty }}</span></div>
            <div class="summary-row"><span class="summary-row__label">Currently Borrowed</span><span class="summary-row__val" style="color:#1678D8">{{ uSelectedItem.borrowedQty||0 }}</span></div>
          </div>
          <div class="form-stack mt-16" v-if="uSelectedItem.availableQty>0">
            <button class="btn btn--primary btn--full" @click="reqItemForm.itemName=uSelectedItem.name+' ('+uSelectedItem.availableQty+' available)'; goUser('request-item')">📋 Request This Item</button>
            <button class="btn btn--ghost btn--full" @click="borrowItemForm.itemName=uSelectedItem.name+' ('+uSelectedItem.availableQty+' available)'; goUser('borrow-equipment')">🤲 Borrow This Item</button>
          </div>
          <div v-else class="info-box mt-16">This item is currently not available for borrowing.</div>
        </div>
      </div>
    </div>

    <!-- ── REQUEST ITEM ── -->
    <div v-else-if="userView==='request-item'">
      <div class="page-header"><div class="page-header__back"><button class="back-btn" @click="goUser('inventory')">←</button><div><h1 class="page-title">Request Item</h1><p class="page-sub">Submit a request for an ICT item</p></div></div></div>
      <div v-if="reqItemDone" class="success-card">
        <div class="success-icon">✓</div>
        <h2>Request Submitted!</h2>
        <p>Your request is pending review by the IT Department. Redirecting to My Requests...</p>
      </div>
      <div v-else class="two-col-sidebar">
        <div class="form-stack">
          <div class="scard p-24">
            <div class="scard__title mb-16">Item Selection</div>
            <div v-if="reqItemError" class="alert alert--err mb-12">{{ reqItemError }}</div>
            <div class="field"><label class="field__label">Select Item to Request <span class="req">*</span></label>
              <select class="field__input" v-model="reqItemForm.itemName">
                <option value="">Select an inventory item</option>
                <option v-for="i in availableItems" :key="i.id">{{ i.name }} ({{ i.availableQty }} available)</option>
              </select>
            </div>
            <div v-if="reqItemForm.itemName&&reqItemForm.itemName!=='Select an inventory item'" class="info-box mt-12">
              <strong>{{ reqItemForm.itemName.split(' (')[0] }}</strong> — {{ availableItems.find(i=>i.name===reqItemForm.itemName.split(' (')[0])?.category||'ICT Equipment' }} — Location: {{ availableItems.find(i=>i.name===reqItemForm.itemName.split(' (')[0])?.location||'—' }}
            </div>
          </div>
          <div class="scard p-24">
            <div class="scard__title mb-16">Request Details</div>
            <div class="form-stack">
              <div class="field"><label class="field__label">Purpose of Request <span class="req">*</span></label><textarea class="field__input field__textarea" v-model="reqItemForm.purpose" placeholder="Describe why you need this item..." rows="3"></textarea></div>
              <div class="two-col-sm">
                <div class="field"><label class="field__label">Date Needed</label><input class="field__input" v-model="reqItemForm.dateNeeded" type="date"/></div>
                <div class="field"><label class="field__label">Duration</label><select class="field__input" v-model="reqItemForm.duration"><option>Select duration</option><option>1 day</option><option>3 days</option><option>1 week</option><option>2 weeks</option><option>1 month</option></select></div>
              </div>
              <div class="field"><label class="field__label">Additional Notes</label><textarea class="field__input field__textarea" v-model="reqItemForm.notes" placeholder="Any additional information..." rows="2"></textarea></div>
            </div>
          </div>
        </div>
        <div class="scard p-24">
          <div class="scard__title mb-16">Request Summary</div>
          <div class="summary-rows">
            <div class="summary-row"><span class="summary-row__label">Item</span><span class="summary-row__val">{{ reqItemForm.itemName?reqItemForm.itemName.split(' (')[0]:'—' }}</span></div>
            <div class="summary-row"><span class="summary-row__label">Date Needed</span><span class="summary-row__val">{{ reqItemForm.dateNeeded?fmtDate(reqItemForm.dateNeeded):'—' }}</span></div>
            <div class="summary-row"><span class="summary-row__label">Duration</span><span class="summary-row__val">{{ reqItemForm.duration==='Select duration'?'—':reqItemForm.duration }}</span></div>
          </div>
          <div class="form-stack mt-16">
            <button class="btn btn--primary btn--full" @click="submitRequestItem">Submit Request</button>
            <button class="btn btn--secondary btn--full" @click="goUser('inventory')">Cancel</button>
          </div>
        </div>
      </div>
    </div>

    <!-- ── BORROW EQUIPMENT ── -->
    <div v-else-if="userView==='borrow-equipment'">
      <div class="page-header"><div class="page-header__back"><button class="back-btn" @click="goUser('inventory')">←</button><div><h1 class="page-title">Borrow Equipment</h1><p class="page-sub">Check out equipment directly</p></div></div></div>
      <div v-if="borrowItemDone" class="success-card">
        <div class="success-icon">✓</div>
        <h2>Borrow Request Submitted!</h2>
        <p>Your borrow request is pending review by the IT Department. Redirecting to My Requests...</p>
      </div>
      <div v-else class="two-col-sidebar">
        <div class="form-stack">
          <div class="scard p-24">
            <div class="scard__title mb-16">Equipment Selection</div>
            <div v-if="borrowItemError" class="alert alert--err mb-12">{{ borrowItemError }}</div>
            <div class="field"><label class="field__label">Select Equipment to Borrow <span class="req">*</span></label>
              <select class="field__input" v-model="borrowItemForm.itemName">
                <option value="">Select available equipment</option>
                <option v-for="i in availableItems" :key="i.id">{{ i.name }} ({{ i.availableQty }} available)</option>
              </select>
            </div>
          </div>
          <div class="scard p-24">
            <div class="scard__title mb-16">Borrowing Details</div>
            <div class="form-stack">
              <div class="two-col-sm">
                <div class="field"><label class="field__label">Borrow Date</label><input class="field__input" v-model="borrowItemForm.borrowDate" type="date"/></div>
                <div class="field"><label class="field__label">Expected Return Date</label><input class="field__input" v-model="borrowItemForm.returnDate" type="date"/></div>
              </div>
              <div class="field"><label class="field__label">Duration</label><select class="field__input" v-model="borrowItemForm.duration"><option>Select duration</option><option>1 day</option><option>3 days</option><option>1 week</option><option>2 weeks</option></select></div>
              <div class="field"><label class="field__label">Purpose / Reason <span class="req">*</span></label><textarea class="field__input field__textarea" v-model="borrowItemForm.purpose" placeholder="Describe the purpose for borrowing this equipment..." rows="3"></textarea></div>
              <div class="field"><label class="field__label">Additional Notes</label><textarea class="field__input field__textarea" v-model="borrowItemForm.notes" placeholder="Any special instructions..." rows="2"></textarea></div>
            </div>
          </div>
          <div class="scard p-24">
            <div class="scard__title mb-12">Borrower Acknowledgement</div>
            <div class="info-box">
              <p style="margin:0 0 10px;font-size:12px;line-height:1.6">{{ settings.terms||'By submitting this borrowing request, I acknowledge that I will be responsible for the equipment while it is in my possession and agree to return it in the same condition.' }}</p>
              <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:12px;font-weight:500"><input type="checkbox" v-model="borrowItemForm.agreed" style="accent-color:#1678D8;width:14px;height:14px"/> I agree to the borrowing terms and conditions</label>
            </div>
          </div>
        </div>
        <div class="scard p-24">
          <div class="scard__title mb-16">Borrow Summary</div>
          <div class="summary-rows">
            <div class="summary-row"><span class="summary-row__label">Equipment</span><span class="summary-row__val">{{ borrowItemForm.itemName?borrowItemForm.itemName.split(' (')[0]:'—' }}</span></div>
            <div class="summary-row"><span class="summary-row__label">Borrow Date</span><span class="summary-row__val">{{ borrowItemForm.borrowDate?fmtDate(borrowItemForm.borrowDate):'—' }}</span></div>
            <div class="summary-row"><span class="summary-row__label">Return Date</span><span class="summary-row__val">{{ borrowItemForm.returnDate?fmtDate(borrowItemForm.returnDate):'—' }}</span></div>
            <div class="summary-row"><span class="summary-row__label">Duration</span><span class="summary-row__val">{{ borrowItemForm.duration==='Select duration'?'—':borrowItemForm.duration }}</span></div>
          </div>
          <div class="form-stack mt-16">
            <button class="btn btn--primary btn--full" @click="submitBorrowItem">Submit Borrow Request</button>
            <button class="btn btn--secondary btn--full" @click="goUser('inventory')">Cancel</button>
          </div>
        </div>
      </div>
    </div>

    <!-- ── MY REQUESTS ── -->
    <div v-else-if="userView==='my-requests'">
      <div class="page-header"><div><h1 class="page-title">My Requests</h1><p class="page-sub">{{ myRequests.length }} total request(s)</p></div></div>
      <div class="scard mb-20">
        <div class="filter-bar">
          <div class="tab-group"><button class="tab" :class="{'tab--on':myReqTab==='All'}" @click="myReqTab='All'">All</button><button class="tab" :class="{'tab--on':myReqTab==='Pending'}" @click="myReqTab='Pending'">Pending</button><button class="tab" :class="{'tab--on':myReqTab==='Approved'}" @click="myReqTab='Approved'">Approved</button><button class="tab" :class="{'tab--on':myReqTab==='Rejected'}" @click="myReqTab='Rejected'">Rejected</button></div>
          <div class="search-wrap"><span class="search-icon">🔍</span><input class="search-input" v-model="myReqSearch" placeholder="Search requests..."/></div>
        </div>
      </div>
      <div class="scard">
        <table class="tbl">
          <thead><tr><th>Request ID</th><th>Item</th><th>Type</th><th>Submitted</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
            <tr v-if="myRequests.length===0"><td colspan="6"><div class="empty"><div class="empty__icon">📄</div><div class="empty__title">No requests yet</div><p class="empty__desc">Use Request Item or Borrow Equipment to submit your first request.</p><div class="flex-row gap-10 mt-12"><button class="btn btn--primary btn--sm" @click="goUser('request-item')">Request Item</button><button class="btn btn--ghost btn--sm" @click="goUser('borrow-equipment')">Borrow Equipment</button></div></div></td></tr>
            <tr v-for="r in myRequests" :key="r.id">
              <td><span class="tbl-link" @click="viewMyRequest(r)">{{ r.requestId }}</span></td>
              <td>{{ r.itemName }}</td><td>{{ r.type }}</td><td style="font-size:12px">{{ r.submittedAt }}</td>
              <td><span class="badge-pill" :class="statusClass(r.status)">{{ r.status }}</span></td>
              <td>
                <div class="action-row">
                  <button class="btn-icon" @click="viewMyRequest(r)">👁️</button>
                  <button v-if="r.status==='Pending'" class="btn btn--danger btn--sm" @click="cancelMyRequest(r)">Cancel</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <div class="tbl-foot"><span class="tbl-count">{{ myRequests.length }} record(s)</span></div>
      </div>
    </div>

    <!-- ── REQUEST DETAIL (user) ── -->
    <div v-else-if="userView==='request-detail'&&selectedMyReq">
      <div class="page-header"><div class="page-header__back"><button class="back-btn" @click="goUser('my-requests')">←</button><div><h1 class="page-title">{{ selectedMyReq.requestId }}</h1><p class="page-sub">Request Details</p></div></div></div>
      <div class="two-col-sidebar">
        <div class="form-stack">
          <div class="scard p-24">
            <div class="scard__title mb-16">Request Information</div>
            <div class="detail-grid">
              <div class="detail-field"><div class="detail-label">Request ID</div><div class="detail-val">{{ selectedMyReq.requestId }}</div></div>
              <div class="detail-field"><div class="detail-label">Type</div><div class="detail-val">{{ selectedMyReq.type }}</div></div>
              <div class="detail-field"><div class="detail-label">Item</div><div class="detail-val">{{ selectedMyReq.itemName }}</div></div>
              <div class="detail-field"><div class="detail-label">Status</div><div class="detail-val"><span class="badge-pill" :class="statusClass(selectedMyReq.status)">{{ selectedMyReq.status }}</span></div></div>
              <div class="detail-field"><div class="detail-label">Submitted</div><div class="detail-val">{{ selectedMyReq.submittedAt }}</div></div>
              <div class="detail-field"><div class="detail-label">Duration</div><div class="detail-val">{{ selectedMyReq.duration||'—' }}</div></div>
              <div class="detail-field" style="grid-column:1/-1"><div class="detail-label">Purpose</div><div class="detail-val">{{ selectedMyReq.purpose||'—' }}</div></div>
              <div class="detail-field" v-if="selectedMyReq.adminNote" style="grid-column:1/-1"><div class="detail-label">Admin Note</div><div class="detail-val">{{ selectedMyReq.adminNote }}</div></div>
              <div class="detail-field" v-if="selectedMyReq.rejectReason" style="grid-column:1/-1"><div class="detail-label">Rejection Reason</div><div class="detail-val" style="color:#E74C3C">{{ selectedMyReq.rejectReason }}</div></div>
            </div>
          </div>
          <div class="scard p-24">
            <div class="scard__title mb-12">What Happens Next?</div>
            <div class="timeline">
              <div class="tl-item tl-item--done"><div class="tl-dot tl-dot--done">✓</div><div><div class="tl-title">Submitted</div><div class="tl-sub">{{ selectedMyReq.submittedAt }}</div></div></div>
              <div class="tl-item" :class="{'tl-item--done':selectedMyReq.status!=='Pending'}"><div class="tl-dot" :class="{'tl-dot--done':selectedMyReq.status==='Approved','tl-dot--err':selectedMyReq.status==='Rejected'}">{{ selectedMyReq.status==='Approved'?'✓':selectedMyReq.status==='Rejected'?'✕':'·' }}</div><div><div class="tl-title">Admin Decision</div><div class="tl-sub">{{ selectedMyReq.status==='Pending'?'Awaiting review by IT Department':selectedMyReq.status }}</div></div></div>
              <div class="tl-item"><div class="tl-dot">·</div><div><div class="tl-title">Pickup / Issue</div><div class="tl-sub">After approval, coordinate with IT Department</div></div></div>
            </div>
          </div>
        </div>
        <div class="form-stack">
          <div class="scard p-24">
            <div style="text-align:center;padding:8px 0">
              <div class="mb-12"><span class="badge-pill" :class="statusClass(selectedMyReq.status)">{{ selectedMyReq.status }}</span></div>
              <button v-if="selectedMyReq.status==='Pending'" class="btn btn--danger btn--full" @click="cancelMyRequest(selectedMyReq); goUser('my-requests')">Cancel Request</button>
              <p v-else class="text-sub" style="font-size:12px">Decision: {{ selectedMyReq.decidedAt||selectedMyReq.submittedAt }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── MY BORROWED ITEMS ── -->
    <div v-else-if="userView==='my-borrowed'">
      <div class="page-header"><div><h1 class="page-title">My Borrowed Items</h1><p class="page-sub">{{ myBorrows.filter(b=>b.status==='Borrowed').length }} active borrowing(s)</p></div></div>
      <div class="scard mb-20"><div class="filter-bar"><div class="tab-group"><button class="tab" :class="{'tab--on':myBorTab==='Active'}" @click="myBorTab='Active'">Active</button><button class="tab" :class="{'tab--on':myBorTab==='Returned'}" @click="myBorTab='Returned'">Returned</button><button class="tab" :class="{'tab--on':myBorTab==='All'}" @click="myBorTab='All'">All</button></div></div></div>
      <div class="scard">
        <table class="tbl">
          <thead><tr><th>Item</th><th>Borrow Date</th><th>Due Date</th><th>Duration</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
            <tr v-if="myBorrows.length===0"><td colspan="6"><div class="empty"><div class="empty__icon">📦</div><div class="empty__title">No borrowed items</div><p class="empty__desc">Items you are currently borrowing will appear here. Use Borrow Equipment to check out an available item.</p></div></td></tr>
            <tr v-for="b in myBorrows" :key="b.id">
              <td>{{ b.itemName }}</td><td>{{ fmtDate(b.borrowDate) }}</td><td>{{ fmtDate(b.dueDate) }}</td><td>{{ b.duration||'—' }}</td>
              <td><span class="badge-pill" :class="statusClass(b.status)">{{ b.status }}</span></td>
              <td>
                <button v-if="b.status==='Borrowed'" class="btn btn--ghost btn--sm" @click="userSubmitReturn(b); goUser('returns')">Return This</button>
                <span v-else class="text-sub">Returned {{ fmtDate(b.returnDate) }}</span>
              </td>
            </tr>
          </tbody>
        </table>
        <div class="tbl-foot"><span class="tbl-count">{{ myBorrows.length }} record(s)</span></div>
      </div>
    </div>

    <!-- ── RETURNS (user) ── -->
    <div v-else-if="userView==='returns'">
      <div class="page-header"><div><h1 class="page-title">Returns</h1><p class="page-sub">Submit and track equipment returns</p></div></div>
      <div class="scard mb-20"><div class="filter-bar"><div class="tab-group"><button class="tab" :class="{'tab--on':uReturnsTab==='Pending Return'}" @click="uReturnsTab='Pending Return'">Pending Return</button><button class="tab" :class="{'tab--on':uReturnsTab==='Return History'}" @click="uReturnsTab='Return History'">Return History</button></div></div></div>
      <div class="scard">
        <template v-if="uReturnsTab==='Pending Return'">
          <table class="tbl">
            <thead><tr><th>Item</th><th>Borrowed On</th><th>Due Date</th><th>Duration</th><th>Action</th></tr></thead>
            <tbody>
              <tr v-if="myPendingReturn.length===0"><td colspan="5"><div class="empty"><div class="empty__icon">↩️</div><div class="empty__title">No items pending return</div><p class="empty__desc">You currently have no active borrowings due for return.</p></div></td></tr>
              <tr v-for="b in myPendingReturn" :key="b.id">
                <td>{{ b.itemName }}</td><td>{{ fmtDate(b.borrowDate) }}</td><td>{{ fmtDate(b.dueDate) }}</td><td>{{ b.duration||'—' }}</td>
                <td><button class="btn btn--primary btn--sm" @click="userSubmitReturn(b)">Submit Return</button></td>
              </tr>
            </tbody>
          </table>
        </template>
        <template v-else>
          <table class="tbl">
            <thead><tr><th>Item</th><th>Returned On</th><th>Condition</th></tr></thead>
            <tbody>
              <tr v-if="myReturnHistory.length===0"><td colspan="3"><div class="empty"><div class="empty__icon">📋</div><div class="empty__title">No return history</div><p class="empty__desc">A record of your completed returns will appear here.</p></div></td></tr>
              <tr v-for="r in myReturnHistory" :key="r.id"><td>{{ r.itemName }}</td><td>{{ fmtDate(r.returnDate) }}</td><td>{{ r.condition||'Good' }}</td></tr>
            </tbody>
          </table>
        </template>
        <div class="tbl-foot"><span class="tbl-count">{{ uReturnsTab==='Pending Return'?myPendingReturn.length:myReturnHistory.length }} record(s)</span></div>
      </div>
    </div>

    <!-- ── NOTIFICATIONS (user) ── -->
    <div v-else-if="userView==='notifications'">
      <div class="page-header"><div><h1 class="page-title">Notifications</h1><p class="page-sub">{{ unreadUserCount }} unread</p></div><button class="btn btn--ghost btn--sm" @click="markAllUserRead">Mark All as Read</button></div>
      <div class="scard p-24">
        <div class="tab-group mb-20"><button class="tab" :class="{'tab--on':userNotifTab==='All'}" @click="userNotifTab='All'">All</button><button class="tab" :class="{'tab--on':userNotifTab==='Requests'}" @click="userNotifTab='Requests'">Requests</button><button class="tab" :class="{'tab--on':userNotifTab==='System'}" @click="userNotifTab='System'">System</button><button class="tab" :class="{'tab--on':userNotifTab==='Unread'}" @click="userNotifTab='Unread'">Unread <span v-if="unreadUserCount>0" class="tab-count">{{ unreadUserCount }}</span></button></div>
        <div v-if="filteredUserNotifs.length===0" class="empty"><div class="empty__icon">🔔</div><div class="empty__title">You're all caught up</div><p class="empty__desc">No notifications at this time. You will be notified here when there are updates on your requests.</p></div>
        <div v-for="n in filteredUserNotifs" :key="n.id" class="notif-item" :class="{'notif-item--unread':!n.read}" @click="markUserNotifRead(n)">
          <div class="notif-dot" v-if="!n.read"></div>
          <div class="notif-body"><div class="notif-msg">{{ n.message }}</div><div class="notif-time">{{ n.createdAt }}</div></div>
        </div>
      </div>
    </div>

    <!-- ── PROFILE ── -->
    <div v-else-if="userView==='profile'">
      <div class="page-header"><div><h1 class="page-title">Profile &amp; Settings</h1><p class="page-sub">Manage your account information</p></div></div>
      <div v-if="profileSaved" class="alert alert--ok mb-20">✓ Profile updated successfully.</div>
      <div v-if="passSaved" class="alert alert--ok mb-20">✓ Password changed successfully.</div>
      <div class="two-col">
        <div class="form-stack">
          <div class="scard p-24">
            <div class="scard__title mb-16">Account Information</div>
            <div class="form-stack">
              <div class="field"><label class="field__label">Full Name</label><input class="field__input" v-model="profileForm.name" type="text" :placeholder="currentUser?.name||'Your full name'"/></div>
              <div class="field"><label class="field__label">Student / Employee ID</label><input class="field__input" v-model="profileForm.idNumber" type="text" placeholder="Your ID number"/></div>
              <div class="field"><label class="field__label">Email Address</label><input class="field__input" v-model="profileForm.email" type="email" placeholder="Your email address"/></div>
              <div class="field"><label class="field__label">Department / Course</label><input class="field__input" v-model="profileForm.department" type="text" placeholder="Your department or course"/></div>
              <button class="btn btn--primary btn--sm" @click="saveProfile">Save Changes</button>
            </div>
          </div>
          <div class="scard p-24">
            <div class="scard__title mb-16">Change Password</div>
            <div v-if="passError" class="alert alert--err mb-12">{{ passError }}</div>
            <div class="form-stack">
              <div class="field"><label class="field__label">Current Password</label><input class="field__input" v-model="passForm.current" type="password" placeholder="Enter current password"/></div>
              <div class="field"><label class="field__label">New Password</label><input class="field__input" v-model="passForm.newPass" type="password" placeholder="Enter new password (min 6 chars)"/></div>
              <div class="field"><label class="field__label">Confirm New Password</label><input class="field__input" v-model="passForm.confirm" type="password" placeholder="Confirm new password"/></div>
              <button class="btn btn--primary btn--sm" @click="savePassword">Update Password</button>
            </div>
          </div>
        </div>
        <div class="form-stack">
          <div class="scard p-24">
            <div class="scard__title mb-16">Notification Preferences</div>
            <div class="form-stack">
              <div class="toggle-row"><span class="toggle-label">Request status updates</span><div class="toggle" :class="{'toggle--on':userPrefs.requestUpdates}" @click="userPrefs.requestUpdates=!userPrefs.requestUpdates"><div class="toggle__knob" :class="{'toggle__knob--on':userPrefs.requestUpdates}"></div></div></div>
              <div class="toggle-row"><span class="toggle-label">System announcements</span><div class="toggle" :class="{'toggle--on':userPrefs.systemAnnouncements}" @click="userPrefs.systemAnnouncements=!userPrefs.systemAnnouncements"><div class="toggle__knob" :class="{'toggle__knob--on':userPrefs.systemAnnouncements}"></div></div></div>
              <div class="toggle-row"><span class="toggle-label">Return reminders</span><div class="toggle" :class="{'toggle--on':userPrefs.returnReminders}" @click="userPrefs.returnReminders=!userPrefs.returnReminders"><div class="toggle__knob" :class="{'toggle__knob--on':userPrefs.returnReminders}"></div></div></div>
            </div>
          </div>
          <div class="scard p-24">
            <div class="scard__title mb-12">Account Summary</div>
            <div class="summary-rows">
              <div class="summary-row"><span class="summary-row__label">Username</span><span class="summary-row__val">{{ currentUser?.username }}</span></div>
              <div class="summary-row"><span class="summary-row__label">Role</span><span class="summary-row__val">User</span></div>
              <div class="summary-row"><span class="summary-row__label">Total Requests</span><span class="summary-row__val">{{ myRequests.length }}</span></div>
              <div class="summary-row"><span class="summary-row__label">Active Borrowings</span><span class="summary-row__val">{{ userStats.borrowed }}</span></div>
            </div>
            <button class="btn btn--danger btn--full mt-16" @click="logout">Logout</button>
          </div>
        </div>
      </div>
    </div>

  </main>
</div>

<!-- ╔══════════════════════════════════════════════════════════════╗
     ║  GLOBAL MODALS                                              ║
     ╚══════════════════════════════════════════════════════════════╝ -->

<!-- Stock In Confirm -->
<div v-if="showSIConfirm" class="modal-backdrop" @click.self="showSIConfirm=false">
  <div class="modal">
    <div class="modal__hd"><span class="modal__title">Confirm Stock In</span><button class="modal__close" @click="showSIConfirm=false">✕</button></div>
    <div class="modal__body">
      <div class="info-box mb-16"><strong>Item:</strong> {{ stockInForm.itemName.split(' (')[0] }}<br><strong>Quantity Received:</strong> {{ stockInForm.quantity }}<br><strong>Supplier:</strong> {{ stockInForm.supplier||'—' }}<br><strong>Date:</strong> {{ fmtDate(stockInForm.dateReceived) }}</div>
      <p style="font-size:13px;color:#5D728A;margin-bottom:16px">This will increase the inventory count for the selected item. Are you sure?</p>
      <div class="flex-row gap-10" style="justify-content:flex-end"><button class="btn btn--secondary btn--sm" @click="showSIConfirm=false">Cancel</button><button class="btn btn--success btn--sm" @click="confirmStockIn">✓ Confirm Stock In</button></div>
    </div>
  </div>
</div>

<!-- Stock Out Confirm -->
<div v-if="showSOConfirm" class="modal-backdrop" @click.self="showSOConfirm=false">
  <div class="modal">
    <div class="modal__hd"><span class="modal__title">Confirm Stock Out</span><button class="modal__close" @click="showSOConfirm=false">✕</button></div>
    <div class="modal__body">
      <div class="info-box mb-16"><strong>Item:</strong> {{ stockOutForm.itemName.split(' (')[0] }}<br><strong>Quantity Out:</strong> {{ stockOutForm.quantity }}<br><strong>Reason:</strong> {{ stockOutForm.reason||'—' }}</div>
      <p style="font-size:13px;color:#5D728A;margin-bottom:16px">This will reduce the inventory count. Are you sure?</p>
      <div class="flex-row gap-10" style="justify-content:flex-end"><button class="btn btn--secondary btn--sm" @click="showSOConfirm=false">Cancel</button><button class="btn btn--danger btn--sm" @click="confirmStockOut">Confirm Stock Out</button></div>
    </div>
  </div>
</div>

<!-- Approve Request -->
<div v-if="showApproveModal" class="modal-backdrop" @click.self="showApproveModal=false">
  <div class="modal">
    <div class="modal__hd"><span class="modal__title">Approve Request</span><button class="modal__close" @click="showApproveModal=false">✕</button></div>
    <div class="modal__body">
      <p style="font-size:13px;color:#5D728A;margin-bottom:14px">Approving will notify the requester and update inventory availability.</p>
      <div class="field mb-16"><label class="field__label">Approval Note (optional)</label><textarea class="field__input field__textarea" v-model="approveNote" placeholder="Add a note to the requester..." rows="2"></textarea></div>
      <div class="flex-row gap-10" style="justify-content:flex-end"><button class="btn btn--secondary btn--sm" @click="showApproveModal=false">Cancel</button><button class="btn btn--success btn--sm" @click="confirmApprove">✓ Confirm Approval</button></div>
    </div>
  </div>
</div>

<!-- Reject Request -->
<div v-if="showRejectModal" class="modal-backdrop" @click.self="showRejectModal=false">
  <div class="modal">
    <div class="modal__hd"><span class="modal__title">Reject Request</span><button class="modal__close" @click="showRejectModal=false">✕</button></div>
    <div class="modal__body">
      <p style="font-size:13px;color:#5D728A;margin-bottom:14px">Please provide a reason. The requester will be notified.</p>
      <div class="field mb-16"><label class="field__label">Rejection Reason <span class="req">*</span></label><textarea class="field__input field__textarea" v-model="rejectReason" placeholder="Explain why this request is being rejected..." rows="3"></textarea></div>
      <div class="flex-row gap-10" style="justify-content:flex-end"><button class="btn btn--secondary btn--sm" @click="showRejectModal=false">Cancel</button><button class="btn btn--danger btn--sm" @click="confirmReject">Confirm Rejection</button></div>
    </div>
  </div>
</div>

<!-- Archive Inventory Item -->
<div v-if="showInvDel" class="modal-backdrop" @click.self="showInvDel=false">
  <div class="modal">
    <div class="modal__hd"><span class="modal__title">Archive Item</span><button class="modal__close" @click="showInvDel=false">✕</button></div>
    <div class="modal__body">
      <p style="font-size:13px;color:#5D728A;margin-bottom:20px">Archive <strong>{{ deletingItem?.name }}</strong>? It will be removed from inventory but all records will be preserved.</p>
      <div class="flex-row gap-10" style="justify-content:flex-end"><button class="btn btn--secondary btn--sm" @click="showInvDel=false">Cancel</button><button class="btn btn--danger btn--sm" @click="doDelInv">Archive Item</button></div>
    </div>
  </div>
</div>

<!-- Add/Edit Category -->
<div v-if="showCatModal" class="modal-backdrop" @click.self="showCatModal=false">
  <div class="modal">
    <div class="modal__hd"><span class="modal__title">{{ editingCat?'Edit':'Add' }} Category</span><button class="modal__close" @click="showCatModal=false">✕</button></div>
    <div class="modal__body">
      <div class="form-stack">
        <div class="field"><label class="field__label">Category Name <span class="req">*</span></label><input class="field__input" v-model="catForm.name" type="text" placeholder="e.g. Computers &amp; Laptops"/></div>
        <div class="field"><label class="field__label">Description</label><textarea class="field__input field__textarea" v-model="catForm.description" placeholder="Describe what types of items belong in this category..." rows="2"></textarea></div>
      </div>
      <div class="flex-row gap-10 mt-16" style="justify-content:flex-end"><button class="btn btn--secondary btn--sm" @click="showCatModal=false">Cancel</button><button class="btn btn--primary btn--sm" @click="saveCat">{{ editingCat?'Save Changes':'Add Category' }}</button></div>
    </div>
  </div>
</div>

<!-- Add/Edit Department -->
<div v-if="showDeptModal" class="modal-backdrop" @click.self="showDeptModal=false">
  <div class="modal">
    <div class="modal__hd"><span class="modal__title">{{ editingDept?'Edit':'Add' }} Department</span><button class="modal__close" @click="showDeptModal=false">✕</button></div>
    <div class="modal__body">
      <div class="form-stack">
        <div class="field"><label class="field__label">Department Name <span class="req">*</span></label><input class="field__input" v-model="deptForm.name" type="text" placeholder="e.g. Information Technology Department"/></div>
        <div class="two-col-sm">
          <div class="field"><label class="field__label">Department Code</label><input class="field__input" v-model="deptForm.code" type="text" placeholder="e.g. IT"/></div>
          <div class="field"><label class="field__label">Head / Contact</label><input class="field__input" v-model="deptForm.head" type="text" placeholder="Department head name"/></div>
        </div>
        <div class="field"><label class="field__label">Description</label><textarea class="field__input field__textarea" v-model="deptForm.description" placeholder="Brief description..." rows="2"></textarea></div>
      </div>
      <div class="flex-row gap-10 mt-16" style="justify-content:flex-end"><button class="btn btn--secondary btn--sm" @click="showDeptModal=false">Cancel</button><button class="btn btn--primary btn--sm" @click="saveDept">{{ editingDept?'Save Changes':'Add Department' }}</button></div>
    </div>
  </div>
</div>

<!-- Add/Edit Supplier -->
<div v-if="showSupModal" class="modal-backdrop" @click.self="showSupModal=false">
  <div class="modal">
    <div class="modal__hd"><span class="modal__title">{{ editingSupplier?'Edit':'Add' }} Supplier</span><button class="modal__close" @click="showSupModal=false">✕</button></div>
    <div class="modal__body">
      <div class="form-stack">
        <div class="field"><label class="field__label">Supplier Name <span class="req">*</span></label><input class="field__input" v-model="supplierForm.name" type="text" placeholder="Company or vendor name"/></div>
        <div class="two-col-sm">
          <div class="field"><label class="field__label">Contact Person</label><input class="field__input" v-model="supplierForm.contactPerson" type="text" placeholder="Primary contact name"/></div>
          <div class="field"><label class="field__label">Status</label><select class="field__input" v-model="supplierForm.status"><option>Active</option><option>Inactive</option></select></div>
        </div>
        <div class="two-col-sm">
          <div class="field"><label class="field__label">Email</label><input class="field__input" v-model="supplierForm.email" type="email" placeholder="contact@supplier.com"/></div>
          <div class="field"><label class="field__label">Phone</label><input class="field__input" v-model="supplierForm.phone" type="text" placeholder="+63 000 000 0000"/></div>
        </div>
        <div class="field"><label class="field__label">Address</label><input class="field__input" v-model="supplierForm.address" type="text" placeholder="Supplier address"/></div>
        <div class="field"><label class="field__label">Notes</label><textarea class="field__input field__textarea" v-model="supplierForm.notes" placeholder="Additional notes..." rows="2"></textarea></div>
      </div>
      <div class="flex-row gap-10 mt-16" style="justify-content:flex-end"><button class="btn btn--secondary btn--sm" @click="showSupModal=false">Cancel</button><button class="btn btn--primary btn--sm" @click="saveSupplier">{{ editingSupplier?'Save Changes':'Add Supplier' }}</button></div>
    </div>
  </div>
</div>

<!-- Delete Supplier -->
<div v-if="showSupDelModal" class="modal-backdrop" @click.self="showSupDelModal=false">
  <div class="modal">
    <div class="modal__hd"><span class="modal__title">Delete Supplier</span><button class="modal__close" @click="showSupDelModal=false">✕</button></div>
    <div class="modal__body">
      <p style="font-size:13px;color:#5D728A;margin-bottom:20px">Delete <strong>{{ deletingSupplier?.name }}</strong>? This action cannot be undone.</p>
      <div class="flex-row gap-10" style="justify-content:flex-end"><button class="btn btn--secondary btn--sm" @click="showSupDelModal=false">Cancel</button><button class="btn btn--danger btn--sm" @click="doDeleteSupplier">Delete Supplier</button></div>
    </div>
  </div>
</div>

<!-- Add/Edit User -->
<div v-if="showUserModal" class="modal-backdrop" @click.self="showUserModal=false">
  <div class="modal" style="max-width:520px">
    <div class="modal__hd"><span class="modal__title">{{ editingUser?'Edit':'Add' }} User</span><button class="modal__close" @click="showUserModal=false">✕</button></div>
    <div class="modal__body">
      <div v-if="userFormError" class="alert alert--err mb-12">{{ userFormError }}</div>
      <div class="form-stack">
        <div class="two-col-sm">
          <div class="field"><label class="field__label">Full Name <span class="req">*</span></label><input class="field__input" v-model="userForm.name" type="text" placeholder="Full name"/></div>
          <div class="field"><label class="field__label">Username <span class="req">*</span></label><input class="field__input" v-model="userForm.username" type="text" placeholder="Username"/></div>
        </div>
        <div class="two-col-sm">
          <div class="field"><label class="field__label">Email</label><input class="field__input" v-model="userForm.email" type="email" placeholder="Email address"/></div>
          <div class="field"><label class="field__label">ID Number</label><input class="field__input" v-model="userForm.idNumber" type="text" placeholder="Student/Employee ID"/></div>
        </div>
        <div class="two-col-sm">
          <div class="field"><label class="field__label">Role</label><select class="field__input" v-model="userForm.role"><option>Student</option><option>Faculty</option><option>Staff</option><option>Administrator</option></select></div>
          <div class="field"><label class="field__label">Status</label><select class="field__input" v-model="userForm.status"><option>Active</option><option>Inactive</option></select></div>
        </div>
        <div class="field"><label class="field__label">Department</label>
          <select class="field__input" v-model="userForm.department"><option value="">Select department</option><option v-for="d in departments" :key="d.id">{{ d.name }}</option></select>
        </div>
      </div>
      <div class="flex-row gap-10 mt-16" style="justify-content:flex-end"><button class="btn btn--secondary btn--sm" @click="showUserModal=false">Cancel</button><button class="btn btn--primary btn--sm" @click="saveUser">{{ editingUser?'Save Changes':'Add User' }}</button></div>
    </div>
  </div>
</div>

<!-- Deactivate User -->
<div v-if="showDeactivateModal" class="modal-backdrop" @click.self="showDeactivateModal=false">
  <div class="modal">
    <div class="modal__hd"><span class="modal__title">{{ editingUser?.status==='Active'?'Deactivate':'Activate' }} Account</span><button class="modal__close" @click="showDeactivateModal=false">✕</button></div>
    <div class="modal__body">
      <p style="font-size:13px;color:#5D728A;margin-bottom:20px">{{ editingUser?.status==='Active'?`Deactivating ${editingUser?.name}'s account will prevent them from logging in. You can reactivate anytime.`:`Reactivate ${editingUser?.name}'s account to restore their access.` }}</p>
      <div class="flex-row gap-10" style="justify-content:flex-end"><button class="btn btn--secondary btn--sm" @click="showDeactivateModal=false">Cancel</button><button class="btn btn--danger btn--sm" @click="doDeactivate">{{ editingUser?.status==='Active'?'Deactivate':'Activate' }} Account</button></div>
    </div>
  </div>
</div>

<!-- Reset Settings -->
<div v-if="showResetModal" class="modal-backdrop" @click.self="showResetModal=false">
  <div class="modal">
    <div class="modal__hd"><span class="modal__title">Reset All Settings</span><button class="modal__close" @click="showResetModal=false">✕</button></div>
    <div class="modal__body">
      <p style="font-size:13px;color:#5D728A;margin-bottom:20px">This will revert all system settings to their default values. This action cannot be undone.</p>
      <div class="flex-row gap-10" style="justify-content:flex-end"><button class="btn btn--secondary btn--sm" @click="showResetModal=false">Cancel</button><button class="btn btn--danger btn--sm" @click="doResetSettings">Reset Settings</button></div>
    </div>
  </div>
</div>

<!-- Clear Inventory -->
<div v-if="showClearModal" class="modal-backdrop" @click.self="showClearModal=false">
  <div class="modal">
    <div class="modal__hd"><span class="modal__title">Clear All Inventory Records</span><button class="modal__close" @click="showClearModal=false">✕</button></div>
    <div class="modal__body">
      <p style="font-size:13px;color:#E74C3C;margin-bottom:20px;font-weight:600">⚠️ Warning: This will permanently delete all {{ inventory.length }} inventory records. This action is irreversible.</p>
      <div class="flex-row gap-10" style="justify-content:flex-end"><button class="btn btn--secondary btn--sm" @click="showClearModal=false">Cancel</button><button class="btn btn--danger btn--sm" @click="doClearInventory">Yes, Clear All Records</button></div>
    </div>
  </div>
</div>

</div><!-- #tv-root -->
</template>

<style scoped>
#tv-root { min-height: 100vh; }
</style>
