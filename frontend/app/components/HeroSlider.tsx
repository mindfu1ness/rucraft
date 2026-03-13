"use client";

import Link from "next/link";

const slides = [
  {
    id: 1,
    gradient: "from-emerald-900 via-teal-800 to-cyan-900",
    image: "/developers/main_slider1.png",
    alt: "Основной слайд 1",
  },
  {
    id: 2,
    gradient: "from-stone-800 via-zinc-800 to-slate-800",
    image: "/developers/main_slider2.png",
    alt: "Основной слайд 2",
  },
  {
    id: 3,
    gradient: "from-green-900 via-emerald-800 to-teal-900",
    image: "/developers/main_slider3.png",
    alt: "Основной слайд 3",
  },
];

export function HeroSlider() {
  const loopSlides = [...slides, slides[0]];

  return (
    <section className="hero-slider">
      <div className="hero-slider-track">
        {loopSlides.map((slide, index) => (
          <div
            key={`${slide.id}-${index}`}
            className={`hero-slide hero-slide-${slide.id}`}
          >
            <img
              src={slide.image}
              alt={slide.alt}
              className="hero-slide-image"
            />
          </div>
        ))}
      </div>
      <div className="hero-overlay">
        <h1 className="hero-title">
          САМЫ Й<br />ЛУЧШИЙ САЙЬ
        </h1>
        <Link href="/auth/register" className="hero-cta">
          зАРЕГИСТРИРОВАТЬСЯ
        </Link>
      </div>
      <div className="hero-nav-btns" aria-hidden="true">
        <button type="button" className="hero-nav-btn prev" aria-label="Предыдущий слайд">
          <img src="/developers/strelka.svg" alt="Предыдущий слайд" className="hero-nav-icon" />
        </button>
        <button type="button" className="hero-nav-btn next" aria-label="Следующий слайд">
          <img src="/developers/strelka.svg" alt="Следующий слайд" className="hero-nav-icon" />
        </button>
      </div>
    </section>
  );
}
