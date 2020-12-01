import Glide from "@glidejs/glide";
import axios from "axios";

class HeroSlider {
  constructor() {
    if (document.querySelector(".hero-slider")) {
      // count how many slides there are
      const dotCount = document.querySelectorAll(".hero-slider__slide").length;

      // Generate the HTML for the navigation dots
      let dotHTML = "";
      for (let i = 0; i < dotCount; i++) {
        dotHTML += `<button class="slider__bullet glide__bullet" data-glide-dir="=${i}"></button>`;
      }

      // Add the dots HTML to the DOM
      document
        .querySelector(".glide__bullets")
        .insertAdjacentHTML("beforeend", dotHTML);

      // Actually initialize the glide / slider script

      axios.get("http://localhost:10004/wp-json/wp/v2/slide").then(res => {
        const slides = res.data.length;
        var glideOptions = {
          type: "carousel",
          perView: 1,
        };

        if (slides && slides > 1) glideOptions.autoplay = 3000;

        const glide = new Glide(".hero-slider", glideOptions);
        glide.mount();
      });
    }
  }
}

export default HeroSlider;
