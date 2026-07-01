import Image from 'next/image';
import React from 'react';
import { Artist } from '../../types/artist';

export default function Hero({ artist }: { artist: Artist }) {
  return (
    <header className="relative bg-black text-white">
      <div className="absolute inset-0">
        {artist.coverImage && (
          // eslint-disable-next-line @next/next/no-img-element
          <img src={artist.coverImage} alt={`${artist.name} portada`} className="w-full h-full object-cover" />
        )}
        <div className="absolute inset-0 bg-gradient-to-b from-black/50 to-black/70" />
      </div>
      <div className="relative container mx-auto py-20 px-4">
        <h1 className="font-serif text-4xl md:text-6xl">{artist.name}</h1>
        <p className="mt-2 text-lg">{artist.specialty} · {artist.location}</p>
        <div className="mt-6 flex gap-3">
          <a href={`#contacto`} className="px-4 py-3 border border-white rounded">Contactar</a>
          <a href={`/book/${artist.slug}`} className="px-4 py-3 bg-red-700 rounded text-white">Solicitar contratación</a>
        </div>
      </div>
    </header>
  );
}
