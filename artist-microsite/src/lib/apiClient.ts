import axios from 'axios';
import { Artist } from '../types/artist';

export async function fetchArtist(slug: string): Promise<Artist | null> {
  try {
    const res = await axios.get(`${process.env.API_URL ?? ''}/api/artists/${encodeURIComponent(slug)}`);
    return res.data as Artist;
  } catch (e) {
    return null;
  }
}
