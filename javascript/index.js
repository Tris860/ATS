var signUpBtn=document.getElementById('SignUptoggle');
var loginBtn=document.getElementById('LogIntoggle');
var loginDiv=document.getElementById('login');
var SignUpDiv=document.getElementById('signup');

signUpBtn.addEventListener('click', ()=>{
    loginDiv.style.animation='slide_down 2s 1 forwards ';
    SignUpDiv.style.display='block';
    SignUpDiv.style.animation='slide_down_up 2s 1s 1 forwards ';
    loginDiv.addEventListener('animationend', ()=>{
        loginDiv.style.transform='translateY(-200%)';
    }, { once: true }); // Use { once: true } for good practice
     // Use { once: true } to remove the listener after it fires once
});

loginBtn.addEventListener('click',()=>{
    SignUpDiv.style.animation='slide_down 2s 1 forwards ';
    loginDiv.style.display='block';
    loginDiv.style.animation='slide_down_up 2s  1s 1 forwards ';
    // Immediately hide SignUpDiv
    loginDiv.addEventListener('animationend', ()=>{
        SignUpDiv.style.transform='translateY(-200%)';
    }, { once: true }); // Use { once: true } for good practice
});