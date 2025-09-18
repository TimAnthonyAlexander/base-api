import { useState, useMemo } from 'react';
import {
  TextField,
  Autocomplete,
  Box,
  Typography,
  InputAdornment,
} from '@mui/material';
import { Search as SearchIcon } from '@mui/icons-material';
import { useNavigate } from 'react-router-dom';
import { NAV, type NavNode } from '../data/nav';

interface SearchResult {
  title: string;
  path: string;
  section: string;
}

function flattenNav(nodes: NavNode[], section = ''): SearchResult[] {
  const results: SearchResult[] = [];
  
  for (const node of nodes) {
    if (node.path) {
      results.push({
        title: node.title,
        path: node.path,
        section: section || node.title,
      });
    }
    
    if (node.children) {
      results.push(...flattenNav(node.children, section || node.title));
    }
  }
  
  return results;
}

export default function Search() {
  const [searchTerm, setSearchTerm] = useState('');
  const navigate = useNavigate();

  const searchResults = useMemo(() => {
    return flattenNav(NAV);
  }, []);

  const filteredResults = useMemo(() => {
    if (!searchTerm) return [];
    
    const term = searchTerm.toLowerCase();
    return searchResults.filter(result =>
      result.title.toLowerCase().includes(term) ||
      result.section.toLowerCase().includes(term)
    ).slice(0, 8); // Limit results
  }, [searchTerm, searchResults]);

  const handleSelect = (_: any, value: string | SearchResult | null) => {
    if (value && typeof value === 'object') {
      navigate(value.path);
      setSearchTerm('');
    }
  };

  return (
    <Autocomplete
      freeSolo
      options={filteredResults}
      getOptionLabel={(option) => 
        typeof option === 'string' ? option : option.title
      }
      renderInput={(params) => (
        <TextField
          {...params}
          placeholder="Search docs..."
          size="small"
          variant="outlined"
          InputProps={{
            ...params.InputProps,
            startAdornment: (
              <InputAdornment position="start">
                <SearchIcon sx={{ color: 'text.secondary', fontSize: 20 }} />
              </InputAdornment>
            ),
            sx: {
              backgroundColor: 'background.paper',
              '&:hover': {
                backgroundColor: 'action.hover',
              },
              '&.Mui-focused': {
                backgroundColor: 'background.paper',
              },
            },
          }}
        />
      )}
      renderOption={(props, option) => (
        <Box component="li" {...props}>
          <Box>
            <Typography variant="body2" fontWeight="medium">
              {option.title}
            </Typography>
            <Typography variant="caption" color="text.secondary">
              {option.section}
            </Typography>
          </Box>
        </Box>
      )}
      inputValue={searchTerm}
      onInputChange={(_, value) => setSearchTerm(value)}
      onChange={handleSelect}
      noOptionsText="No results found"
    />
  );
}
