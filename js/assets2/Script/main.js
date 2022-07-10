/**
 * retourne l'object XMLHttpRequest si il est instanciable
 */
function getXMLHttpRequest() {
	var xhr = null;
  
	if (window.XMLHttpRequest || window.ActiveXObject) {
	  if (window.ActiveXObject) {
		try {
		  xhr = new ActiveXObject("Msxml2.XMLHTTP");
		} catch (e) {
		  xhr = new ActiveXObject("Microsoft.XMLHTTP");
		}
	  } else {
		xhr = new XMLHttpRequest();
	  }
	} else {
	  alert("Votre navigateur ne supporte pas l'objet XMLHTTPRequest...");
	  return null;
	}
  
	return xhr;
  }
  
  function htmlEntities(str) {
	  var t = str.replace(/&/gi, "[amp;_");
	  
	   t = t.replace("+","%2B");
  
	   return t;
  }
  
  
  String.prototype.normalize = function(){
	  var accent = [
		  /[\300-\306]/g, /[\340-\346]/g, // A, a
		  /[\310-\313]/g, /[\350-\353]/g, // E, e
		  /[\314-\317]/g, /[\354-\357]/g, // I, i
		  /[\322-\330]/g, /[\362-\370]/g, // O, o
		  /[\331-\334]/g, /[\371-\374]/g, // U, u
		  /[\321]/g, /[\361]/g, // N, n
		  /[\307]/g, /[\347]/g, // C, c
	  ];
	  var noaccent = ['A','a','E','e','I','i','O','o','U','u','N','n','C','c'];
	   
	  var str = this;
	  for(var i = 0; i < accent.length; i++){
		  str = str.replace(accent[i], noaccent[i]);
	  }
	   
	  return str;
  }
  
  
  function optimize(xhr){
	  document.getElementById('Error').innerHTML="";
	  if(xhr==null){
		  if(confirm("want you run the database optimizer ?")){
			  request(optimize, "optimize=true");
			  alert("Please wait about 60sec.");
			  
		  }
		  
	  }
	  else{
		  
		  if(xhr.length>10){
			  document.getElementById('Error').innerHTML=xhr;
		  }
		  else{
			  
			  alert("Optimize completed in "+xhr+" sec.");
		  }
	  }
  }
  
  function optimizeException(xhr){
	  document.getElementById('Error').innerHTML="";
	  if(xhr==null){
		  if(confirm("want you run the database optimizer ?")){
			  request(optimizeException, "optimizeException=true");
			  alert("Please wait about 60sec.");
			  
		  }
		  
	  }
	  else{
		  
		  if(xhr.length>10){
			  document.getElementById('Error').innerHTML=xhr;
		  }
		  else{
			  
			  alert("Optimize completed in "+xhr+" sec.");
		  }
	  }
  }
  
  function directAccess(item,e){
	  
	  if(e.keyCode==13 && item.value!=""){
		  var link="https://tofu-prod.bc/?View=360&key="+item.value;
		  window.open(link,"_blank");
	  }
  
  }
  
  function setloading(action,nbrQuery){
	var loading='loadingSpinner';
	var nbrOfQuery='loadingNbrQuery';

	if(nbrQuery==1){
		chronoReset();
		chronoStart();
	}

	if(action == true){
		document.getElementById(nbrOfQuery).innerHTML = nbrQuery;
		document.getElementById(loading).style.top = '2.5%';
		
	}
	else{
		chronoStop();
		document.getElementById(nbrOfQuery).innerHTML = nbrQuery;
		document.getElementById(loading).style.top = '-10%';
		setTimeout(function(){	
			chronoReset();
		}, 500);
	}

  }
  
  /**
   * execute l'object XMLHttpRequest, quand readystate est ok, passe le retour du serveur dans la fonction de callback.
   * 
   * @param function callback function a utiliser au retour de l'object XMLHttpRequest
   * @param string value les jeux de clé/valeur a ajouter au _GET
   */
  function request(callback, value, item, url, code ) {
	 
	  if(url==null)url='ajax.php';
	  var timer=Date.now();
	  listxhr.push(timer);
	 
	  var j=0;
			  for (let i = 0; i < listxhr.length; i++) {
				  const element = listxhr[i];
				  
				  if(element!=null){
					  j++
				  }
			  }
			  setloading(true,j);
			  	
			  
				  
	var xhr = getXMLHttpRequest();
  
	xhr.onreadystatechange = function () {
  
	  if (xhr.readyState == 4 && (xhr.status == 200 || xhr.status == 0)) {
			  callback(xhr.responseText, item);
			  
			  
			  for (let i = 0; i < listxhr.length; i++) {
				  const element = listxhr[i];
				  
				  if(element==timer){
					  delete listxhr[i];
					  break;
				  }
			  }
			  var j=0;
			  for (let i = 0; i < listxhr.length; i++) {
				  const element = listxhr[i];
				  
				  if(element!=null){
					  j++
				  }
			  }
  
			  if(j==0){
				  listxhr=[];
				 setloading(false,0);
			  }
			  else{
				setloading(true,j);
			  }
  
  
			  var callable=false;
  
			  var whatcallable='';
			  var list = document.querySelectorAll('script');
			  for (let i = 0; i < list.length; i++) {
				  const element = list[i];
				  if(element.hasAttribute('AJAX_KEY')==true && element.getAttribute('AJAX_KEY')==code){
					  eval(element.innerText);
					  callable=true;
				  }
			  }
			  var table = $('table.FixHeader');
		  	if(table!=null) table.floatThead();
	  }
	  };
	  
	  code = Date.now();
  
	xhr.open("POST", url, true);
  
	xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhr.send(value+'&AJAX_KEY='+code);
  }
  
  function updateGuideAndHelp(xhr){
	  document.getElementById("GuideAndHelp").innerHTML = xhr ;
  }
  
  function request_corfu(callback, value, item) {
  
	var xhr = getXMLHttpRequest();
  
	xhr.onreadystatechange = function () {
  
	  if (xhr.readyState == 4 && (xhr.status == 200 || xhr.status == 0)) {
			  callback(xhr.responseText, item);
			  
	  }
	};
  
	xhr.open("POST", "../ajax.php", true);
  
	xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhr.send(value);
  }
  
  function callable(xhr,item,callback,args) {
	  if(xhr!=null){
		  document.getElementById(item).innerHTML = xhr ;
	  }
	  else{
		  var element = document.getElementById(item);
		  var minimumDelay=element.getAttribute('minimumDelay');
		  var lastUpdate=element.getAttribute('lastUpdate');
		  var actualTime=Math.round(Date.now() / 1000);
			  
		  //console.log('minimumDelay : '+minimumDelay);
		  //console.log('lastUpdate : '+lastUpdate);
		  //console.log('actualTime: '+actualTime);
		  var calc = (parseInt(lastUpdate)+parseInt(minimumDelay))-parseInt(actualTime);
		  //console.log('refresh : '+calc);
		  
		  if(calc<0){
		  //	console.log('Request:'+callback);
			  element.setAttribute('lastUpdate',actualTime);
			  var link = document.location.href;
			  link = link.replace(/view/gi,'Callable');
			  request(callable, "Request=" + callback+"&"+htmlEntities(args), item,link);
		  }
	  
	  }
	  
   
  }
  
  function updateGuideAndHelp(xhr){
	  document.getElementById("GuideAndHelp").innerHTML = xhr ;
  }
  
  function request_corfu(callback, value, item) {
  
	var xhr = getXMLHttpRequest();
  
	xhr.onreadystatechange = function () {
  
	  if (xhr.readyState == 4 && (xhr.status == 200 || xhr.status == 0)) {
			  callback(xhr.responseText, item);
			  
	  }
	};
  
	xhr.open("POST", "../ajax.php", true);
  
	xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhr.send(value);
  }
  
  /**
   * fonction qui callback qui recupere le string et l'envoi dans un elements du dom
   * @param String xhr 
   */
  function loading(xhr) {
	if (xhr >= 99) {
	  document.getElementById("cached").innerHTML = xhr + "% charched (FULL)";
	  document.getElementById("loading").style.width = xhr + "%";
	  document.getElementById("loading").style.borderRadius = "0 0 0 0";
	  window.setTimeout(function () { request(loading, "caching=true"); }, 100000);
	}
	else {
	  document.getElementById("loading").style.borderRadius = "0 20px 20px 0";
	  document.getElementById("cached").innerHTML = xhr + "% charched";
	  document.getElementById("loading").style.width = xhr + "%";
	  window.setTimeout(function () { request(loading, "caching=true"); }, 0);
	}
  }
  
  function filter(item) {
	if (item.checked == true) {
		
	  var keygen = item.getAttribute("keygen");
	  var note = htmlEntities(document.getElementById('Note_'+keygen).value);
	  var hOptions = document.getElementsByName('HandledOption_'+keygen);
	  var hOption = 0;
	  
	  
	  
	  for(i=0;i<hOptions.length;i++){
		  if(hOptions[i].checked ==true){
			  hOption=hOptions[i].value;
		  }
	  }
	  
	  if(hOption==0 && hOptions.length>0){
		  alert ("Please select an option!");
		  return;
	  }
	  
  
	  request(updateException, "validException=true&ref=" + item.getAttribute("ref") + "&keygen=" + item.getAttribute("keygen") + '&wo_id=' + item.getAttribute("wo_id") + '&note=' + note + '&handleoption=' + hOption, item);
	}
  
  
  }

  function addSpecialRules(xhr,item) {

	if (item!=null) {
		
	  var url = item.getAttribute("CallableURL");
	 
	  //request(addSpecialRules, "addSpecialRules=true&ref=" + item.getAttribute("ref") + "&keygen=" + item.getAttribute("keygen") + '&status=' + item.checked , item,url);
	  request(updateException, "addSpecialRules=true&ref=" + item.getAttribute("ref") + "&keygen=" + item.getAttribute("keygen") + '&status=' + item.checked , item,url);
	}
  
  }
  
  function updateException(xhr, item) {
	if (xhr != "") {
		
		console.log(xhr);

	  requestupdate();
	  
	  var t = item.name;
	  item.style.opacity="0";
	 
	  request(updateExceptionView, "updateException=true&key=" + item.getAttribute("ref"),item.getAttribute("ref"));
	  var key = item.getAttribute("ref");
  
	}
	else {
	  item.checked = false;
	}
  }
  
  function updateException2(xhr) {
	if (xhr != "") {
  
	  var element = document.getElementById("Comment");
	  element.innerHTML = xhr;
  
	  //document.getElementById(item.name).style.opacity = "0";
	}
   // else {
	 // item.checked = false;
	//}
  }
  
  
  function delAllCache(xhr) {
	if (xhr == 1) {
	  request(loading, "caching=true");
	}
  }
  
  function reload(xhr, item) {
  
	if (xhr == 1 || xhr == true || xhr == "") {
	  requestupdate();
	  window.location.reload();
	  document.getElementById(item.id).style.opacity = "0";
	}
	else {
	  document.getElementById("ajax").innerHTML = xhr;
	}
  }
  
  function selectException(value,manual=false) {


	var list2 = document.getElementsByName("viewException");
	var list=[];

	for (var i = 0; i < list2.length; i++) {
		var element = list2[i];
			
		if(element.hasAttribute("Manual")==manual){	
			list.push(element);
		}
	}
	
	
	  if(!isNaN(value)){
		  
		  
		  for (var i = 0; i < list.length; i++) {
			  var element = list[i];
			  
			  if (value == true) {	
				  element.checked = true;
			  }
			  else {
				  element.checked = false;
			  }
		  }
	  }
	  
	  else{
			  
			  for (var i = 0; i < list.length; i++) {
				var element = list[i];
				
				if(element.getAttribute("Category")==value){	
					element.checked = true;
				}
		 	 }
		  
		  
	  }
   
	search("");
  }

  function checkuncheck(item,attr){
	 var list = document.getElementsByClassName(attr);

	 for (var i = 0; i < list.length; i++) {
		var element = list[i];
			
		element.checked = item.checked;
		
	  }
  }
  
  function changeValidity(me, key,wo_id) {
	var v = document.getElementById(key).value;
	var k = me.getAttribute("key");
	var e = me.getAttribute("exception");
   
	request(updateExceptionView, "changeValidity=true&key=" + k + "&exception=" + e + "&validity=" + v + "&wo_id=" + wo_id,k);
	
  }
  
  function updateExceptionView(xhr,key) {
	if (xhr != "") {
		  addComment2(key);
	  document.getElementById("Exception").innerHTML = xhr;
	  
		  $(function(){ 
			  $.switcher('.ONOFF');
		  });
	  
	}
  }
  
  function search(item, event) {
  
	var verif = 0;
  
	if (item != null && item.name == "selectColumn") {
	  var t = item.value.search("Date");
	  
	  if (item.value.search("Date") != -1) {
		document.getElementsByName("searchText")[0].type = 'date';
	  }
	  else {
		document.getElementsByName("searchText")[0].type = 'text';
	  }
	}
  
	if (item != null && item.name == "searchText" && item.type != "date") {
	  if (event.keyCode == 13) {
		verif = 1;
	  }
	} else {
	  verif = 1;
	}
  
	if (item != null && item.name == "selectColumn") {
	  verif = 1;
	}
  
	if (verif == 1) {
  
	  wait("View", true);
	  numberOfRequest(1);
	  request(updateView, prepareSearch());
	}
  
  }
  
  function addGOAndSite(item, event, key) {
  
	if (item.value != "" && item.value.search("_") != -1) {
	  var value = item.value;
	  request(reload, "addGOAndSite=" + value);
	}
  
	if (item.name == "addOfferManual") {
	  offer = document.getElementsByName("offer")[0].value;
	  site = document.getElementsByName("site")[0].value.toUpperCase();
	  if (site == "") {
		site = "A00";
	  }
	  if (offer.length >= 6 && site.length == 3) {
  
		request(reload, "addGOAndSite=" + offer + "_" + site + "_" + key);
	  }
	}
  
	if (item.name == "no_offer" && key != null) {
	  request(reload, "addGOAndSite=null_null_" + key);
  
	}
	
	if (item.name == "addReferenceBpost" && key != null) {
		reference = document.getElementById('bpost').value;
	   
	  request(reload, "addReferenceBpost=true&key="+key+"&reference="+reference);
  
	}
  }
  
  function prepareSearch() {
	  
	  var filter = document.getElementsByName("filter")[0].value;
	  
	var selectColumn = document.getElementsByName("selectColumn")[0].value;
	var searchText = document.getElementsByName("searchText")[0].value;
	var archive = document.getElementsByName("archive")[0].checked;
	var workArea = "";
	var calendar = "";
	var owner = "";
	var subowner = "";
	var searchviewtemplate = "";
	var exception = "";
  
	  //if(archive==true)document.getElementsByName("archive")[0].checked=false;
  
	  // selection de la vue
	var list = document.getElementsByClassName("SearchViewTemplate");
  
	for (var i = 0; i < list.length; i++) {
	  if (list[i].checked == true) {
		
		searchviewtemplate = list[i].value;
	  }
	}
	  // composition des areas
	var list = document.getElementsByClassName("check_workarea");
  
	for (var i = 0; i < list.length; i++) {
	  if (list[i].checked == true) {
		if (workArea != "") workArea += ",";
		workArea += list[i].value;
	  }
	}
	  
	  // composition des calendriers
	var list = document.getElementsByClassName("check_calendar");
  
	for (var i = 0; i < list.length; i++) {
	  if (list[i].checked == true) {
		if (calendar != "") calendar += ",";
		calendar += list[i].value;
	  }
	}

	  // composition des owners
	var list = document.getElementsByClassName("check_Owner");
  
	for (var i = 0; i < list.length; i++) {
	  if (list[i].checked == true) {
		if (owner != "") owner += ",";
		owner += list[i].value;
	  }
	}

	  // composition des subowners
	var list = document.getElementsByClassName("check_SubOwner");
  
	for (var i = 0; i < list.length; i++) {
	  if (list[i].checked == true) {
		if (subowner != "") subowner += ",";
		subowner += list[i].value;
	  }
	}
  
	  // composition des exceptions
	var list = document.getElementsByName("viewException");
  
	for (var i = 0; i < list.length; i++) {
	  if (list[i].checked == true) {
		if (exception != "") exception += ",";
		exception += list[i].value;
	  }
	}
  
	var str = "search_view=true&searchText=" + searchText + "&selectColumn=" + selectColumn + "&filter=" + filter + "&exception=" + exception + "&workArea=" + workArea + "&Calendar=" + calendar + "&Owner=" + owner + "&SubOwner=" + subowner + "&archive=" + archive + "&SearchViewTemplate=" + searchviewtemplate;
	return str;
  }
  function wait(item, active) {
	if (item != null && document.getElementById(item) != null) {
  
	  if (active == true) {
  
		document.getElementById(item).style.opacity = "0.1";
	  }
	  else {
		document.getElementById(item).style.opacity = "1";
	  }
	}
  }
  
  function updateView(xhr) {
	if (xhr != "") {
	  numberOfRequest(-1);
	  if (numberOfRequest() == 0) {
		document.getElementById("View").innerHTML = xhr;
			var table = $('table.FixHeader');
		  if(table!=null) table.floatThead();
		wait("View", false);
	  }
	}
  }
  
  function viewWorkLoad(sha1) {
	  if(sha1.length>100){
		  var str = sha1;
	  }
	  else
	  {
		  var str = document.getElementById(sha1).innerHTML;
	  }
	var element = document.getElementById("ViewWorkLoad"); 
	var old = element.innerHTML;
	if(str==old || (sha1.length>100 && old.length>100)){
	  element.innerHTML="";
	  element.style.opacity = "0";
	  element.style.zIndex = "0";
	  element.style.padding = "0";
	  element.style.margin = "0";
	}
	else{
	  element.innerHTML = str;
	  element.style.opacity = "1";
	  element.style.zIndex = "10";
	  element.style.padding = "10px";
	  element.style.margin = "15px";
	}
  }
  
  function search_CdbID(key){
	   request(viewWorkLoad, "search_CdbID="+key);
  }
  
  function update() {
	  
	  var result = localStorage.getItem("update");
  
	if (document.getElementById("ViewResult") != null) {
	  
	  localStorage.setItem("update", false);
	  wait("View", true);
	  request(updateView, prepareSearch()+"&refresh=true");
	}
  
  }
  
  function updateNotWait() {
	  
	  var result = localStorage.getItem("update");
  
	if (document.getElementById("ViewResult") != null) {
	  
	  localStorage.setItem("update", false);
	  wait("View", false);
	  request(updateView, prepareSearch()+"&refresh=true");
	}
  
  }
  
  function requestupdate() {
	  
	  localStorage.setItem("update", true);
  }
  
  function unforget(item, event) {
	if (item.type == "tr") {
  
	  alert(item);
	}
  }
  
  function archiving(item, event) {
	var check = "0";
	if (item.checked == true) {
	  check = "1";
	}
	request(updateArchiving, "key=" + item.value + "&archiving=" + check, item);
  }
  
  function addFolder(item) {
	
	request(reload, "key=" + item.value + "&createFolder=" + item, item);
  }
  
  
  function addComment2(key) {
	var str = document.getElementById('addComment').value;
	str = htmlEntities(str);
	request(updateException2, "key=" + key + "&comment=" + str);
  }
  
  function test() {
  
	request(test2, "Order_Id=335494003");
  }
  function test2(xhr) {
  
	document.getElementById("test").innerHTML = xhr;
  }
  
  function copy() {
  
	var toCopy = document.getElementById('');
  
	toCopy.select();
	document.execCommand('copy',false);
  
  
  
  }
  
  function updateArchiving(xhr, item) {
	if (xhr == "ON") {
	  item.parentElement.parentElement.parentElement.className = "Low Exception";
	}
	else if (xhr == "OFF") {
	  item.parentElement.parentElement.parentElement.className = "Hight Exception";
	}
	else {
	  alert(xhr);
	}
	request(updateExceptionView, "updateException=true&key=" + item.getAttribute("value"));
	requestupdate();
  }
  function numberOfRequest(value) {
	var key = "numberOfRequest";
  
	if (value == 0) {
  
	  localStorage.setItem(key, 0);
	}
  
	else if (value != null) {
	  var oldValue = localStorage.getItem(key);
	  var calc = parseInt(oldValue) + parseInt(value);
  
	  if (calc < 0) {
		calc = 0;
	  }
  
	  localStorage.setItem(key, parseInt(calc));
	}
  
	return localStorage.getItem(key);
  
  }
  
  function routage(me,item){
	  if(me==null)return;
	  var max=5;
	  
	  var V = document.getElementById(me.id).value.toLowerCase();
	  
	  
		  var list = document.getElementById(item);
		  var listRoutage = document.getElementById('listRoutage');
		  
		  listRoutage.length=0;
		  
		  for (var i=0; i<list.options.length;i++){
			  
			  var s = list.options[i].value.toLowerCase();
			  
			  if(s.normalize().search(V.normalize())!=-1 && V!=""){
				  var nb = s.search(V);
				  
				  var option = document.createElement("option");
				  option.text = list.options[i].value;
				  option.setAttribute("Key",list.options[i].getAttribute("Key"));
				  listRoutage.add(option); 
				  
			  }
		  }
		  
		  listRoutage.className="";
		  
		  if(listRoutage.length==0 || selectedRoutage()==document.getElementById('searchRoutage')){
			  //listRoutage.style.display="none";
		  }
		  else{
			  listRoutage.style.display="block";
			  if(listRoutage.length>max){
				  listRoutage.size=max;
			  }
			  else{
				  listRoutage.size=listRoutage.length;
			  }
		  }
	  var item = selectedRoutage();
	  cachelist();
  }
  
  function cachelist(itemSelected){
	  var listRoutage = document.getElementById('listRoutage');
	  var search = document.getElementById('searchRoutage').value;
		  if(listRoutage.length==0 || itemSelected==search){
			  listRoutage.style.display="none";
		  }
		  else{
			  listRoutage.style.display="block";
			  if(listRoutage.length>max){
				  listRoutage.size=max;
			  }
			  else{
				  listRoutage.size=listRoutage.length;
			  }
		  }
  }
  
  function refreshDB(xhr){
	  
	  var list = document.getElementsByName('UpdateStatus');
	  var isgood=true;
  
	  for (let index = 0; index < list.length; index++) {
		  const element = list[index];
		  if(element.getAttribute('isgood')!='true'){
			  isgood=false;
		  }
	  }
  
	  if (list!=null){
		  if(xhr==null || isgood==false){
			  window.setTimeout(function () { request(refreshDB, "&refreshDB=true" ); }, 60000);	
		  }
		  if(xhr!=null ){
			  var item = document.getElementById('refreshDB');
			  item.innerHTML=xhr;
		  }
	  }
  }
  
  function importUpdate(xhr){
	  var val = document.getElementById('importUpdate');
	  
	  if(typeof xhr != 'string'){
		  
		  request(importUpdate, "&importUpdate=true" ); 
	  }		
	  else{
		  var item = document.getElementById('importUpdate');
		  item.innerHTML=xhr;
	  }
	  
	  
  }
  
  function selectedRoutage(){
	  var item = document.getElementById("listRoutage");
	  for (var i=0; i<item.options.length;i++){
		  
		  if(item.options[i].selected==true){
			  var item2 = item.options[i].getAttribute("Key");
			  //.getElementById("searchRoutage").value=item.options[i].value;
			  //cachelist(item.options[i].value);
			  return item2;
		  }
	  }
	  
  }
  
  function folderRoutage(){
	  return document.getElementById('dataRoutage').getAttribute('Key');
  }
  
  function addRoutage(xhr){
	  if(xhr==null){
		  var min=10;
		  
		  var folder=folderRoutage();
		  var destination=selectedRoutage();
		  var comment = document.getElementById('commentRoutage').value;
		  
		  if(folder!=null && destination!=null && comment.length>min){
			  requestupdate();
			  request(addRoutage, "addRoutage=true&folder="+folder+"&destination="+destination+"&comment="+comment);
		  }
		  else{
			  if(folder==null)alert("Error Key unknow!");
			  else if(destination==null)alert("Selection person missing!");
			  else if(comment.length<min)alert("Min "+min+" caracters!");
		  }
	  }
	  else{
		  document.getElementById('Assign to').innerHTML=xhr;
	  }
  }
  
  function closeRoutage(key){
	  requestupdate();
	  request(addRoutage, "closeRoutage="+key);
  }
  
  function getEmployees(value,item){
	  
	  request(getEmployeesReturn, "getEmployees="+value.value,item);
	  
  }
  
  function addValueFeedback(item,atribut,update){
	  var index=document.getElementById('peertargetreturn').selectedIndex;
	  var tmp = document.getElementById('peertargetreturn')[index].getAttribute(atribut);
	  var peer = document.getElementById('peertargetreturn')[index].getAttribute('peer');
	  var t = document.getElementById(item);
	  
	  if(t.value!="")t.value=t.value+'; ';
	  t.value=t.value+tmp;
	  
	  if(update==true){
		  
		  request(getProcessFeedback, "getProcessFeedback="+peer);
	  }
  }
  
  function addValuesFeedback(item,searchID,atribut,target,target2,update){
	  var index=document.getElementById(searchID).selectedIndex;
	  var tmp = document.getElementById(searchID)[index].getAttribute(atribut);
	  var peer = document.getElementById(searchID)[index].getAttribute('peer');
	  var t = document.getElementById(item);
	  var target = document.getElementById(target);
	  var target2 = document.getElementById(target2);
	  
	  if(update==true){
		  
		  t.value = tmp;
		  target.value = peer;
		  request(getProcessFeedback, "getProcessFeedback="+peer,target2);
	  }
	  else{
		  if(t.value!="")t.value=t.value+'; ';
		  t.value=t.value+tmp;
	  }
  }
  
  
  function addIndispo(item,peer,day){
	  var desc		= document.getElementById('description').value;
	  var startHour 	= document.getElementsByName('startHour')[0].value;
	  var startMin 	= document.getElementsByName('startMin')[0].value;
	  var endHour 	= document.getElementsByName('endHour')[0].value;
	  var endMin 		= document.getElementsByName('endMin')[0].value;
	  var classitem 	= document.getElementsByName('class');
	  var classChecked="";
	  for( var i=0; i < classitem.length; i++){
		  
		  if(classitem[i].checked==true)classChecked = classitem[i].value;
	  }
	  
	  if(desc.length>1){	
		  request(refreshIndispo, "setIndispo=true&peer="+peer+"&day="+day+"&description="+desc+"&startHour="+startHour+"&startMin="+startMin+"&endHour="+endHour+"&endMin="+endMin+"&class="+classChecked,item);
	  }
	  else{
		  alert("add text for description please...");
	  }
  }
  
  function deleteIndispo(item,id){
	  
	  item.parentElement.innerHTML="";
	  request(refreshIndispo, "deleteIndispo=true&id="+id);
	  
  }
  
  function refreshIndispo(xhr,item){
	  if(xhr!=""){
		  item.parentElement.innerHTML=xhr;
	  }
	  
  }
  
  function deleteDelegation(peer){
	  
	  request(refreshDelegation, "deleteDelegation=true&peer="+peer);
	  
  }
  
  function CorfuGraph(xhr,subDistrict,day,product){
	  
	  if(xhr==null){
		  request_corfu(CorfuGraph, "CorfuGraph=true&subDistrict="+subDistrict+"&day="+day+"&product="+product);
	  }
	  else{
		  if(xhr.length>10){
			  alert(xhr);
		  }else{
			  window.open('http://el2194.bc/TOFU/DITCH/graph.php','Graph');
		  }
		  
	  }
	  
  }
  
  function refreshLOG(xhr,folder){
	  
	  if(xhr==null){
		  setTimeout(function(){ request(refreshLOG, "refreshLOG=true&folder="+folder,folder); }, 60000);
				  document.getElementById('Online').opacity=0;
				  document.body.backgroundColor= 'white';
	  }
	  else {
		  setTimeout(function(){ request(refreshLOG, "refreshLOG=true&folder="+folder,folder); }, 60000);
		  document.getElementById('Online').innerHTML=xhr;
		  document.getElementById('Online').opacity=1;
		  document.body.backgroundColor= 'red';
	  }
  }
  
  
  function addDelegation2(me){
	  var peer = document.getElementById('peer').value;
	  if(peer>10000){
		  request(refreshDelegation, "addDelegation=true&peer="+peer);
	  }
	  else{
		  alert('peer number invalide!');
	  }
	  
  }
  
  function refreshDelegation(xhr){
	  
	  
	  document.getElementById('listDelegation').innerHTML=xhr;
	  
	  
  }
  
  function getEmployeesReturn(xhr,item){
	  
	  document.getElementById(item).innerHTML=xhr;	
  }
  
  function getProcessFeedback(xhr,item){
	  if(item==null)document.getElementById('sendto').value=xhr;
	  else item.value=xhr;
  }
  
  function showchart(){
	  var str = chart.getHidden();
	  alert(str);
  }
  
  
  function displayWOID(version,start,end,aarea,calendar,per,cdir,keyrequest,arearequest){
	  document.getElementById('listWOID').innerHTML='Loading...';
	  request(updateWOID, "displayWOID=true&version="+version+"&start="+start+"&end="+end+"&area="+aarea+"&calendar="+calendar+"&per="+per+"&cdir="+cdir+"&keyrequest="+keyrequest+"&arearequest="+arearequest);
  }
  
  
  function updateWOID(xhr){
	  document.getElementById('listWOID').innerHTML=xhr;
  }
  
  function ajustEnd(me,item){
	  var a = me.value;
	  var z = document.getElementsByName(item)[0].value;
	  if(z=='' || z<a){
		  document.getElementsByName(item)[0].value = a;
	  }
  }
  
  
  function freeView(subDistrict){
	  var list = document.getElementsByClassName('FreeView_'+subDistrict);
	  
	  for(var i=0;i<list.length;i++){
		  var unused	= list[i].getAttribute('unused');
		  var used	= list[i].getAttribute('used');
		  var value 	= list[i].innerHTML;
		  
		  if(value==used){
			  list[i].innerHTML = unused;
		  }
		  else{
			  list[i].innerHTML = used;
		  }
	  }
  }
  
  function RefreshInTime(){
	  var t = new Date();
	  var hour = t.getHours();
	  var minute = t.getMinutes();
	  if(hour>oldhour && minute>26){
		  oldhour=hour;
		  location.reload();
	  }
  }
  
  function FeedbackupdateInfo(reference,idtarget){
	  
	  request(FeedbackupdateInfo_callback, "FeedbackupdateInfo=true&reference="+reference.value,idtarget);
	  
  }
  
  function FeedbackupdateInfo_callback(xhr,idtarget){
	  
	  document.getElementById(idtarget).innerHTML=xhr;
	  
  }
  
  //sticky
  window.onscroll = function() {myFunction()};
  var header;
  var sticky=[];
  window.onload = function() {prepaSticky()};
  
  function prepaSticky(){
	  header = document.getElementsByClassName("Sticky");
  }
  
  function myFunction() {
	  for(var i=0;i<header.length;i++){
		  if (window.pageYOffset > header[i].offsetTop) {
			  header[i].classList.add("sticky");
		  } else {
			  header[i].classList.remove("sticky");
		  }
	  }
	  
  }
  
  function setfocus() {
	  var list = document.getElementsByClassName('Focus');
	  for (let i = 0; i < list.length; i++) {
		  const item = list[i];
		  item.focus();
	  }
  }
  
  function hiddenItem(item){
	  var element = document.getElementById(item);
   
		  element.innerHTML="";
		  element.style.opacity = "0";
	   element.style.zIndex = "0";
	   element.style.padding = "0";
	   element.style.margin = "0";
   }

   function getSubOwner(xhr,item,url, owner,subowner) {
	
	if (xhr==null) {
		var item = document.getElementById(owner);
		var item2 = document.getElementById(subowner);
		var select=0;
		for (var i=0; i<item.options.length;i++){
			
			if(item.options[i].selected==true){
				select = item.options[i].getAttribute("value");
				break;
			}
		}
		request(getSubOwner, "owner=" + select,item2,url);
	}
	else{
		item.innerHTML=xhr;
		
	}
	
  }

   function getSubOwner2(xhr,item,url,source) {
	
	if (xhr==null) {
		item = document.getElementById(source);
		request(getSubOwner2, prepareSearch(),item,url);
	}
	else{
		item.innerHTML=xhr;
		search(this);
		
	}
	
  }
  
  var listxhr=[];
  window.addEventListener("load", setfocus);
  var oldt = new Date();
  var oldhour = oldt.getHours();
  var oldlaunch="";
  numberOfRequest(0);
  if(document.getElementById("focus")!=null)window.addEventListener("focus", updateNotWait);
  document.getElementById('clickimportUpdate').addEventListener("click",importUpdate);
  refreshDB();
  wait("View", true);
  request(updateView, prepareSearch());
  routage(null,null);
  
  