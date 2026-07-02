import type { NextApiRequest, NextApiResponse } from 'next';
import prisma from '../../lib/prisma';

const API_KEY = process.env.ARTIST_API_SECRET;

export default async function handler(req: NextApiRequest, res: NextApiResponse) {
  const { slug } = req.query as { slug: string };
  if (req.method === 'GET') {
    const artist = await prisma.artist.findUnique({ where: { slug }, include: { gallery: true, shows: true, events: true, services: true, press: true, reviews: true } });
    if (!artist) return res.status(404).json({ error: 'Not found' });
    return res.status(200).json(artist);
  }

  if (req.method === 'PUT') {
    const key = req.headers['x-api-key'] as string | undefined;
    const externalUserId = req.headers['x-external-user-id'] as string | undefined;
    if (!API_KEY || key !== API_KEY) return res.status(401).json({ error: 'Unauthorized' });
    if (!externalUserId) return res.status(400).json({ error: 'Missing external user id' });

    const user = await prisma.user.findUnique({ where: { externalId: externalUserId } });
    if (!user || user.artistId === null) return res.status(403).json({ error: 'Forbidden' });
    if (user.artistId !== (await prisma.artist.findUnique({ where: { slug } }))?.id) return res.status(403).json({ error: 'Not owner' });

    const data = req.body;
    try {
      const updated = await prisma.artist.update({ where: { slug }, data });
      return res.status(200).json(updated);
    } catch (e) {
      console.error(e);
      return res.status(500).json({ error: 'server_error' });
    }
  }

  res.setHeader('Allow', 'GET, PUT');
  return res.status(405).end();
}
