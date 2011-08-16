/*
 * raidplan js
 */
function update_group_id_state()
  {
      if( document.getElementById('calELevel').value == 1 )
      {
          document.getElementById('calGroupId[]').disabled=false;
      }
      else
      {
          document.getElementById('calGroupId[]').disabled=true;
      }
  }

  Date.prototype.getWeek = function() 
  {
	    var determinedate = new Date();
	    determinedate.setFullYear(this.getFullYear(), this.getMonth(), this.getDate());
	    var D = determinedate.getDay();
	    if(D == 0) D = 7;
	    determinedate.setDate(determinedate.getDate() + (4 - D));
	    var YN = determinedate.getFullYear();
	    var ZBDoCY = Math.floor((determinedate.getTime() - new Date(YN, 0, 1, -6)) / 86400000);
	    var WN = 1 + Math.floor(ZBDoCY / 7);
	    return WN;
	}

  /*  make a XMLHTTP Request object */
  function GetXmlHttpObject() 
  { 
  	var xmlhttp=false;	
  	
  	try
  	{ 
  		//  IE7+, Firefox, Chrome, Opera, Safari
  		xmlhttp=new XMLHttpRequest();
  	}
  	catch(e)	
  	{	// activex code for IE6, IE5
  		try
  		{			
  			xmlhttp= new ActiveXObject("Microsoft.XMLHTTP");
  		}
  		catch(e)
  		{
  			try
  			{
  				xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
  			}
  			catch(e1)
  			{
  				xmlhttp=false;
  			}
  		}
  	}
  	return xmlhttp;
  }

  /* ajax function to fill profile, sends call to server */ 
  var xmlhttp; 
  function update_roles(role)
  {
	  xmlhttp = GetXmlHttpObject();
	  if (xmlhttp == null)
	  {
		  return;  
	  }
	   var strURL="{UA_AJAXHANDLER1}?role="+role;
	   xmlhttp.onreadystatechange=stateChanged;
	   xmlhttp.open("GET", strURL, true);
	   // send to server
	   xmlhttp.send(null);
  }


  /* called from update_roles when state changed */
  
  function stateChanged()
  {
  	if (xmlhttp.readyState==4) //request complete
  	{
  		if (xmlhttp.status == 200)
  		{
  			//receive xml
  			xmlDoc=xmlhttp.responseXML;
  			var root = xmlDoc.getElementsByTagName('rolelist')[0];
  			var roles = root.getElementsByTagName("role")
  			document.getElementById('raidroles').innerHTML = "";
  			for (var i = 0; i < roles.length; i++)
  			{
				var role = roles[i];
				var role_id = role.getElementsByTagName("role_id")[0].firstChild.nodeValue;
				var role_name = role.getElementsByTagName("role_name")[0].firstChild.nodeValue;
				var role_needed = role.getElementsByTagName("role_needed")[0].firstChild.nodeValue;

  				var otitle = document.createElement('dt');

  				var oLabel = document.createElement('label');
				oLabel.innerHTML = role_name + ":";
				oLabel.setAttribute("for","subject");
				otitle.appendChild(oLabel);
				
				var odef = document.createElement('dd');
				var oInput1 = document.createElement('input');
				var oInput2 = document.createElement('input');
				oInput1.setAttribute("type","hidden");
				oInput1.setAttribute("name", "role[" + role_id + "]");
  				oInput1.setAttribute("value", role_needed);
  				oInput2.setAttribute("type","text");
  				oInput2.setAttribute("name", "role_needed[" + role_id + "]");
  				oInput2.setAttribute("size", "5");
  				oInput2.setAttribute("maxlength", "2");
  				oInput2.setAttribute("tabindex", "2");
  				oInput2.setAttribute("value", role_needed);
  				oInput2.setAttribute("class", "inputbox autowidth");

  				odef.appendChild(oInput1);
  				odef.appendChild(oInput2);

  	  			/* now insert it in the dom. raidroles in the div anchor */
  				document.getElementById("raidroles").appendChild(otitle);
  				document.getElementById("raidroles").appendChild(odef);
  			}
  			

  		}
  		else 
  		{
     	   		alert("{LA_ALERT_AJAX}:\n" + xmlhttp.statusText);
  	 	}
  	}
  }