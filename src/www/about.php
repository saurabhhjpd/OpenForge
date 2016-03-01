<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');


$HTML->header(array('title'=>"About"));


?>
<div class="span12">
	<div class="span2">&nbsp;</div>
	<div class="span8 justifyalg">
		<div class="spandata"></div>
	<div class="spandata">
    <h2  class="headtext">About OpenForge</h2>
<hr noshade="">
		<p>
			DigiLocker is a key initiative under Digital India, the Indian Government’s flagship program aimed at transforming India into a digitally empowered society and knowledge economy. DigiLocker ties into Digital India’s visions areas of providing citizens a shareable private space on a public cloud and making all documents / certificates available on this cloud. 
		</P>
		
		<p>
			Targeted at the idea of paperless governance, DigiLocker is a platform for issuance and verification of documents & certificates in a digital way, thus eliminating the use of physical documents. Indian citizens who sign up for a DigiLocker account get a dedicated cloud storage space that is linked to their Aadhaar (UIDAI) number. Organizations that are registered with Digital Locker can push electronic copies of documents and certificates (e.g. driving license, Voter ID, School certificates) directly into citizens lockers. Citizens can also upload scanned copies of their legacy documents in their accounts. These legacy documents can be electronically signed using the eSign facility.
		</P>
		<p>
			<strong class="stronghead">The platform has the following benefits:</strong>
		</P>
		<ol>
			<li>Citizens can access their digital documents anytime, anywhere and share it online. This is convenient and time saving.</li>
			<li>It reduces the administrative overhead of Government departments by minimizing the use of paper.</li>
			<li>Digital Locker makes it easier to validate the authenticity of documents as they are issued directly by the registered issuers.</li>
			<li>Self-uploaded documents can be digitally signed using the eSign facility (which is similar to the process of self-attestation).</li>
		</ol>
		<p>
		<strong class="stronghead">The following are the key stakeholders in the DigiLocker system:</strong>
        </p>
		<ul>
		<li> <strong>Issuer:</strong> Entity issuing e-documents to individuals in a standard format and making them electronically available e.g. CBSE, Registrar Office, Income Tax department, etc.</li>
		<li><strong>Requester:</strong> Entity requesting secure access to a particular e-document stored within a repository (e.g. University, Passport Office, Regional Transport Office, etc.)</li>
		<li> <strong>Resident:</strong> An individual who uses the Digital Locker service based on Aadhaar number.</li>
     </ul>
		
		<p>
		<strong class="stronghead">The main technology components of the DigiLocker system are:</strong>
		<li> <strong>Repository:</strong> Collection of e-documents that is exposed via standard APIs for secure, real-time access.</li>
		<li> <strong>Access Gateway:</strong> Secure online mechanism for requesters to access e-documents from various repositories in real-time using URI (Uniform Resource Indicator).</li>
		<li> <strong>DigiLocker Portal:</strong> Dedicated cloud based personal storage space, linked to each resident’s Aadhaar for storing e-documents, or URIs of e-documents.</li>
		</P>
		<p>
		More details on DigiLocker are available at <a href="http://10.22.119.29/resourcecenter.php">http://10.22.119.29/resourcecenter.php</a>
		</P>
	<br>
    <br>
	</div>
</div>	
</div>	
<?php $HTML->footer(array()); ?>