import { GetServerSideProps } from 'next';
import Head from 'next/head';
import SEO from '../../components/seo/SEO';
import NavSticky from '../../components/layout/NavSticky';
import Hero from '../../components/artist/Hero';
import Bio from '../../components/artist/Bio';
import { Artist } from '../../types/artist';
import { fetchArtist } from '../../lib/apiClient';

export const getServerSideProps: GetServerSideProps = async (ctx) => {
  const slug = ctx.params?.slug as string;
  const artist = await fetchArtist(slug);
  if (!artist) return { notFound: true };
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
