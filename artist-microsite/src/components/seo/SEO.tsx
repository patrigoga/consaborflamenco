import Head from 'next/head';
import React from 'react';

export default function SEO({ title, description, jsonLd }: { title?: string; description?: string; jsonLd?: object }) {
  return (
    <Head>
      {title && <title>{title}</title>}
      {description && <meta name="description" content={description} />}
      <meta property="og:type" content="profile" />
      {description && <meta property="og:description" content={description} />}
      {jsonLd && <script type="application/ld+json">{JSON.stringify(jsonLd)}</script>}
    </Head>
  );
}
