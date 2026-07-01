import React from 'react';

export default function Bio({ content }: { content?: string }) {
  return (
    <div className="prose max-w-none px-4 py-12 container mx-auto" dangerouslySetInnerHTML={{ __html: content ?? '<p>Sin biografía.</p>' }} />
  );
}
