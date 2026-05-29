/* eslint-disable react-hooks/exhaustive-deps */
import { useCallback, useEffect, useRef, useState } from 'react';

export function useAsync(callback, deps = []) {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const callbackRef = useRef(callback);

  useEffect(() => {
    callbackRef.current = callback;
  }, [callback]);

  const run = useCallback(async (...args) => {
    setLoading(true);
    setError(null);
    try {
      const result = await callbackRef.current(...args);
      setData(result);
      return result;
    } catch (err) {
      setError(err);
      throw err;
    } finally {
      setLoading(false);
    }
  }, []);

  // The caller controls when this async effect reruns through `deps`.
  // Schedule `run` asynchronously to avoid triggering setState synchronously inside the effect.
  useEffect(() => {
    const id = setTimeout(() => {
      run().catch(() => {});
    }, 0);

    return () => clearTimeout(id);
  }, deps);

  return { data, setData, loading, error, refresh: run };
}
