import type { NextApiRequest, NextApiResponse } from 'next';
import prisma from '../../../lib/prisma';

const API_KEY = process.env.ARTIST_API_SECRET;

export default async function handler(req: NextApiRequest, res: NextApiResponse) {
  if (req.method !== 'POST') return res.status(405).end();
  const key = req.headers['x-api-key'] as string | undefined;
  if (!API_KEY || key !== API_KEY) return res.status(401).json({ error: 'Unauthorized' });

  const { externalUserId, email, name, slug } = req.body;
  if (!externalUserId || !slug || !name) return res.status(400).json({ error: 'externalUserId, slug and name required' });

  try {
    // find or create user
    let user = await prisma.user.findUnique({ where: { externalId: externalUserId } });
    if (!user) {
      user = await prisma.user.create({ data: { externalId: externalUserId, email, name } });
    }

    // find or create artist
    let artist = await prisma.artist.findUnique({ where: { slug } });
    if (!artist) {
      artist = await prisma.artist.create({ data: { slug, name, stageName: name } });
    }

    // link if not linked
    if (!user.artistId) {
      await prisma.user.update({ where: { id: user.id }, data: { artistId: artist.id } });
    }

    return res.status(200).json({ ok: true, artist });
  } catch (e) {
    console.error(e);
    return res.status(500).json({ error: 'server_error' });
  }
}
