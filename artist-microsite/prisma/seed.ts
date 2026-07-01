import { PrismaClient } from '@prisma/client';

const prisma = new PrismaClient();

async function main() {
  const existing = await prisma.artist.findFirst({ where: { slug: 'la-nina-flamenca' } });
  if (existing) {
    console.log('Seed: artist already exists');
    return;
  }

  const artist = await prisma.artist.create({
    data: {
      slug: 'la-nina-flamenca',
      name: 'La Niña Flamenca',
      stageName: 'La Niña',
      specialty: 'Bailaora',
      location: 'Sevilla, España',
      bio: '<p>La Niña Flamenca es una bailaora con amplia trayectoria...</p>',
      heroImage: '/images/sample-hero.jpg',
      coverImage: '/images/sample-cover.jpg',
      social: { instagram: 'https://instagram.com/la_nina' },
      meta: { description: 'Bailaora profesional de flamenco.' }
    }
  });

  await prisma.galleryImage.createMany({
    data: [
      { artistId: artist.id, url: '/images/gallery1.jpg', alt: 'Foto 1' },
      { artistId: artist.id, url: '/images/gallery2.jpg', alt: 'Foto 2' }
    ]
  });

  console.log('Seed completed');
}

main()
  .catch((e) => { console.error(e); process.exit(1); })
  .finally(() => prisma.$disconnect());
