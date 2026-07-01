import { GetServerSideProps } from 'next';
import Head from 'next/head';
import SEO from '../../components/seo/SEO';
import NavSticky from '../../components/layout/NavSticky';
import Hero from '../../components/artist/Hero';
import Bio from '../../components/artist/Bio';
import { Artist } from '../../types/artist';
import prisma from '../../lib/prisma';

export const getServerSideProps: GetServerSideProps = async (ctx) => {
  const slug = ctx.params?.slug as string;
  const artistRaw = await prisma.artist.findUnique({
    where: { slug },
    include: { gallery: true, shows: true, events: true, services: true, press: true, reviews: true }
  });
  if (!artistRaw) return { notFound: true };
  const artist = JSON.parse(JSON.stringify(artistRaw)) as Artist;
  return { props: { artist } };
};

export default function ArtistPage({ artist }: { artist: Artist }) {
  return (
    <>
      <SEO title={`${artist.name} • ${artist.specialty ?? ''}`} description={artist.meta?.description} jsonLd={{}} />
      <NavSticky sections={[ 'inicio','biografia','espectaculos','galeria','videos','agenda','prensa','servicios','opiniones','contacto' ]} />
      <main>
        <Hero artist={artist} />
        <section id="biografia"><Bio content={artist.bio} /></section>
        {/* other sections to be implemented */}
      </main>
    </>
  );
}
