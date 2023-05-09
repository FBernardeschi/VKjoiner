let blockNavLink = document.querySelector('.nav-line');
let casper = document.querySelector('.casper');
let main = document.querySelector('main');

blockNavLink.addEventListener('click', function(e) {
    if(e.target.dataset.left) {
        casper.style.left = e.target.dataset.left;
        main.style.right = e.target.dataset.right;
    }
})