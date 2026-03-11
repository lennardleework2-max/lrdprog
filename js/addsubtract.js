function add_calculator()
{
	 
 n1=parseFloat(document.getElementById("add_1").value)
 n2=parseFloat(document.getElementById("add_2").value)
 sum=n1+n2
 document.getElementById("addition_1").value=sum
 
}

function sub_tract()
{
 
 n1=parseFloat(document.getElementById("add_1").value)
 n2=parseFloat(document.getElementById("add_2").value)
 difference=n1-n2
 document.getElementById("addition_1").value=difference
 
}

function multiply()
{
 
 n1=parseFloat(document.getElementById("add_1").value)
 n2=parseFloat(document.getElementById("add_2").value)
 product=n1*n2
 document.getElementById("addition_1").value=product
 
}

function divide()
{
 
 n1=parseFloat(document.getElementById("add_1").value)
 n2=parseFloat(document.getElementById("add_2").value)
 quotient=n1/n2
 document.getElementById("addition_1").value=quotient
 
}



