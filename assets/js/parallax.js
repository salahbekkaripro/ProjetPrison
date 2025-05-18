document.addEventListener("mousemove", (e) => {
    const layers = document.querySelectorAll(".parallax-layer");
    const x = (e.clientX - window.innerWidth / 2) / 100;
    const y = (e.clientY - window.innerHeight / 2) / 100;
  
    layers.forEach((layer, index) => {
      const depth = (index + 1) * 5;
      layer.style.transform = `translate3d(${x * depth}px, ${y * depth}px, 0)`;
    });
  });
  