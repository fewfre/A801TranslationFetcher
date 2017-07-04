(function(){
	let game = "transformice";
	let lang = "en";
	
	/**************
	* Event Listeners
	**************/
	addEventListenerAll(document.querySelectorAll(".js-game-chosen"), "click", onGameClicked);
	addEventListenerAll(document.querySelectorAll(".js-lang-chosen"), "click", onLangClicked);
	addEventListenerAll(document.querySelectorAll(".js-lang-chosen-custom"), "click", onCustomLangEntered);
	addEventListenerAll(document.querySelectorAll(".js-reset"), "click", onResetClicked);
	
	function onGameClicked(e) {
		setActiveStep("lang-step");
		game = e.target.dataset.game;
	}
	
	function onLangClicked(e) {
		lang = e.target.dataset.lang;
		doRetrieval();
	}
	
	function onCustomLangEntered(e) {
		lang = document.querySelector("#lang_code").value;
		if(lang.length < 2) { return; }
		doRetrieval();
	}
	
	function doRetrieval() {
		setActiveStep("fetch-step");
		fetchI18nData(game, lang, (pData) => {
			addDataToPage(pData);
		}, doDataRetreivalError);
	}
	
	function onResetClicked(e) {
		setActiveStep("game-step");
	}
	
	/**************
	* Methods
	**************/
	function setActiveStep(pID) {
		let tSteps = document.querySelectorAll(".step");
		for(tStep of tSteps) {
			tStep.classList.remove("active");
		}
		document.querySelector("#"+pID).classList.add("active");
	}
	
	function addDataToPage(pData) {
		if(!pData) {
			doDataRetreivalError("Server returned no content.");
		}
		setActiveStep("result-step");
		// let tLines = pData;
		pData = pData.replace(/¤$/, ''); // Replace extra one at end
		let tLines = pData.split("¤");
		// let tLines = pData.split("&#164;");
		let tHTML = "";
		tHTML += "<table class='result-table'>";
		// Add table head
		tHTML += "<thead>";
			tHTML += "<tr>";
			tHTML += "<th>Unique Key</th>";
			tHTML += "<th>Translation</th>";
			tHTML += "</tr>";
		tHTML += "</thead>";
		// Add table contents
		tHTML += "<tbody>";
		let tKey, tMessage;
		for(tLine of tLines) {
			[tKey, tMessage] = splitOnce(tLine, "=");
			tMessage = highlightSyntaxAll(tMessage);
			tMessage = `<pre>${tMessage}</pre>`;
			tHTML += `<tr><th><div class="overflow">${tKey}</div></th><td>${tMessage}</td></tr>`;
		}
		tHTML += "</tbody>";
		tHTML += "</table>";
		document.querySelector("#result").innerHTML = tHTML;
	}
	
	function doDataRetreivalError(pMessage) {
		setActiveStep("error-step");
		document.querySelector("#error").innerHTML = pMessage;
	}
	
	/**************
	* Helper Methods
	**************/
	function fetchI18nData(pGame, pLang, pCallback, pFailCallback) {
		let tUrl = `fetch_translation_file.php?game=${pGame}&lang=${pLang}&format=text`;
		// fetch(tUrl, {
		// 	method:"GET",
		// })
		// .then(function(response) { return response.text(); })
		// .then(function(text){
		// 	pCallback(text);
		// });
		fewAjax({
			url:tUrl, method:"GET", dataType:"text",
			success:pCallback, fail:pFailCallback,
		});
	}
	
	function addEventListenerAll(pList, pEventName, pCallback) {
		for(tElem of pList) {
			tElem.addEventListener(pEventName, pCallback);
		}
	}
	
	function highlightSyntaxAll(pString) {
		pString = highlightHTML(pString);
		pString = highlightStringSubstitution(pString);
		pString = highlightSex(pString);
		pString = highlightSpeaker(pString);
		pString = highlightDialogBreak(pString);
		return pString;
	}
	
	function highlightHTML(pString) {
		// let tTitle = htmlEscape(pString);
		// return `<span title='${tTitle}'>${highlightHTMLRecurrsive(pString)}</span>`;
		return `<!-- ${pString} -->${highlightHTMLRecurrsive(pString)}`
		// return pString.replace(/</g, "&lt;").replace(/>/g, "&gt;");
	}
	function highlightHTMLRecurrsive(pString) {
		let tRegex = /<(\w+)(?:\s*(.*?))>((?:.|\n)*?)((?:<\/\1>|$))/g;
		let tMatches = tRegex.exec(pString);
		if(tMatches) {
			let [tAllMatched, tTag, tAttribs, tContent, tEnd] = tMatches;
			console.log(tMatches);
			tContent = highlightHTMLRecurrsive(tContent);
			let tFormattedCode;
			switch(tTag) {
				case "img":
					tFormattedCode = `<img ${tAttribs} title="${htmlEscape(tAttribs)}" />${tContent}`;
					break;
				case "font":
					if(tAttribs.indexOf("color=") > -1) {
						tContent = `<font ${tAttribs}>${tContent}</font>`;
					}
					// Fall through
				default:
					tAttribs = tAttribs ? ` <span class='attr'>${tAttribs}</span>` : "";
					tEnd = tEnd ? `<span class='tag'>&lt;/${tTag}&gt</span>` : "";
					tFormattedCode = `<span class='tag'>&lt;${tTag}${tAttribs}&gt;</span>${tContent}${tEnd}`;
					break;
			}
			pString = pString.replace(tAllMatched, tFormattedCode);
		}
		return pString;
	}
	
	function highlightStringSubstitution(pString) {
		return pString.replace(/(\%\d)/g, "<span class='subst'>$1</span>");
	}
	
	function highlightSex(pString) {
		return pString.replace(/\((.*?)\|(.*?)\)/g, "<span class='sex'>(<span class='m'>$1</span>|<span class='f'>$2</span>)</span>");
	}
	
	function highlightSpeaker(pString) {
		return pString.replace(/(^#[A-Za-z_]*:)/gm, "<span class='speaker'>$1</span>");
	}
	
	function highlightDialogBreak(pString) {
		return pString.replace(/(_P_)/g, "<span class='d-break'>$1</span>");
	}
	
	// pData = { url:String, method:String="GET", dataType:String="text", contentType?:String, success?:String->Void, fail?:String->Void }
	function fewAjax(pData) {
		pData.method = pData.method || "GET";
		pData.dataType = pData.dataType || "text";
		
		let xmlhttp = new XMLHttpRequest();

		xmlhttp.onreadystatechange = function() {
			if (xmlhttp.readyState == XMLHttpRequest.DONE ) {
				if (xmlhttp.status == 200) {
					if(pData.success) {
						let tResponse = xmlhttp.responseText;
						try {
							switch(pData.dataType) {
								case "json": tResponse = JSON.parse(tResponse); break;
							}
						} catch(e) {
							if(pData.fail) { pData.fail("Error parsing data as "+pData.dataType); }
							return;
						}
						pData.success(tResponse);
					}
				}
				else {
					if(pData.fail) { pData.fail("Error "+xmlhttp.status+": "+xmlhttp.responseText); }
				}
			}
		};

		xmlhttp.open(pData.method, pData.url, true);
		if(pData.contentType) xmlhttp.setRequestHeader('Content-Type', pData.contentType);
		xmlhttp.send();
	}
	
	function htmlEscape(pString) {
		return pString.replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&#34;").replace(/'/g, "&#39;");
	}
	
	// Only split first instance of something in a string. ex: "a-b-c-d" split on "-" would return "a" and "b-c-d"
	function splitOnce(pString, pSeparator) {
		let tSplitIndex = pString.indexOf(pSeparator);
		return [ pString.substring(0, tSplitIndex), pString.substring(tSplitIndex+pSeparator.length) ];
	}
})();
