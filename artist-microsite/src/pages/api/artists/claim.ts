import type { NextApiRequest, NextApiResponse } from 'next';
import prisma from '../../../lib/prisma';

const API_KEY = process.env.ARTIST_API_SECRET;

export default async function handler(req: NextApiRequest, res: NextApiResponse) {
  if (req.method !== 'POST') return res.status(405).end();
  const key = req.headers['x-api-key'] as string | undefined;
  if (!API_KEY || key !== API_KEY) return res.status(401).json({ error: 'Unauthorized' });

  const { externalUserId, email, name, slug } = req.body as {
    externalUserId?: string;
    email?: string | null;
    name?: string;
    slug?: string;
  };
  if (!externalUserId || !slug || !name) return res.status(400).json({ error: 'externalUserId, slug and name required' });

  try {
    const normalizedSlug = String(slug).trim().toLowerCase();
    if (!normalizedSlug) {
      return res.status(400).json({ error: 'invalid_slug' });
    }

    // find or create user by stable external id
    let user = await prisma.user.findUnique({ where: { externalId: externalUserId } });
    if (!user) {
      user = await prisma.user.create({ data: { externalId: externalUserId, email, name } });
    } else if ((email && user.email !== email) || user.name !== name) {
      user = await prisma.user.update({
        where: { id: user.id },
        data: { email: email ?? user.email, name }
      });
    }

    const claimedArtist = user.artistId
      ? await prisma.artist.findUnique({ where: { id: user.artistId } })
      : null;
    const artistBySlug = await prisma.artist.findUnique({ where: { slug: normalizedSlug } });

    let artist = claimedArtist;

    if (artistBySlug && claimedArtist && artistBySlug.id !== claimedArtist.id) {
      return res.status(409).json({ error: 'slug_already_taken' });
    }

    if (!artist && artistBySlug) {
      const owner = await prisma.user.findFirst({ where: { artistId: artistBySlug.id } });
      if (owner && owner.externalId !== externalUserId) {
        return res.status(409).json({ error: 'artist_already_claimed' });
      }
      artist = artistBySlug;
    }

    if (!artist) {
      artist = await prisma.artist.create({ data: { slug: normalizedSlug, name, stageName: name } });
    }

    if (artist.slug !== normalizedSlug || artist.name !== name || artist.stageName !== name) {
      artist = await prisma.artist.update({
        where: { id: artist.id },
        data: { slug: normalizedSlug, name, stageName: name }
      });
    }

    if (user.artistId !== artist.id) {
      user = await prisma.user.update({ where: { id: user.id }, data: { artistId: artist.id } });
    }

    return res.status(200).json({ ok: true, artist });
  } catch (e) {
    console.error(e);
    return res.status(500).json({ error: 'server_error' });
  }
}
